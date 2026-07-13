<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vérification automatisée de la migration legacy MyTicketO → Primea.
 * À lancer APRÈS l'import (notamment lors de la bascule finale) pour garantir
 * qu'aucune donnée n'a été perdue et — le plus critique — que TOUT billet
 * legacy reste scannable (son `ref` existe comme `code` en Primea).
 *
 * Code de sortie non nul si un écart critique est détecté (utilisable comme
 * garde automatique avant go-live).
 */
class LegacyVerify extends Command
{
    protected $signature = 'legacy:verify {--all : Vérifier aussi les événements passés}';

    protected $description = 'Vérifier l\'intégrité de la migration legacy (comptes + scannabilité des billets)';

    public function handle(): int
    {
        try {
            if (!Schema::connection('legacy')->hasTable('leweb_ticket')) {
                $this->error('Tables legacy absentes : charger le dump avant de vérifier.');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Connexion legacy impossible : ' . $e->getMessage());
            return self::FAILURE;
        }

        $eventIds = $this->scopeEventIds($this->option('all'));
        $lc = fn () => DB::connection('legacy');

        // Comptes legacy (périmètre) vs importés.
        $rows = [
            ['Événements',
                count($eventIds),
                DB::table('events')->whereNotNull('legacy_id')->count()],
            ['Billets',
                $lc()->table('leweb_ticket')->whereIn('id_event', $eventIds)->count(),
                DB::table('tickets')->whereNotNull('legacy_id')->count()],
            ['Paiements PAID',
                $lc()->table('leweb_pay')->whereIn('id_event', $eventIds)->where('statut', 'PAID')->count(),
                DB::table('orders')->whereNotNull('legacy_id')->count()],
            ['Clients',
                $lc()->table('leweb_users')->count(),
                DB::table('users')->whereNotNull('legacy_id')->count()],
        ];

        $this->table(['Entité', 'Legacy (périmètre)', 'Importé'], array_map(
            fn ($r) => [$r[0], $r[1], $r[2] . ($r[2] < $r[1] ? '  ⚠' : '  ✓')],
            $rows
        ));

        // CRITIQUE : billets legacy dont le ref n'est PAS scannable en Primea.
        $refs = $lc()->table('leweb_ticket')->whereIn('id_event', $eventIds)
            ->whereNotNull('ref')->where('ref', '<>', '')->pluck('ref');
        $missing = 0;
        foreach ($refs->chunk(2000) as $chunk) {
            $present = DB::table('tickets')->whereIn('code', $chunk->all())->pluck('code')->flip();
            foreach ($chunk as $ref) {
                if (!isset($present[$ref])) {
                    $missing++;
                }
            }
        }

        if ($missing > 0) {
            $this->error("❌ {$missing} billet(s) legacy NON scannable(s) en Primea (ref absente). Migration incomplète.");
            return self::FAILURE;
        }

        $this->info('✓ Tous les billets legacy du périmètre sont scannables en Primea (ref = code préservé).');
        return self::SUCCESS;
    }

    /** Mêmes règles de périmètre que legacy:import. */
    private function scopeEventIds(bool $all): array
    {
        $events = DB::connection('legacy')->table('leweb_event')->where('sup', 0)->get();
        if ($all) {
            return $events->pluck('id')->all();
        }
        $dates = DB::connection('legacy')->table('leweb_date')->where('sup', 0)->get()->groupBy('id_event');
        $ids = [];
        foreach ($events as $ev) {
            foreach (($dates->get($ev->id) ?? collect()) as $d) {
                if ($d->date_format && strtotime($d->date_format) > time()) {
                    $ids[] = $ev->id;
                    break;
                }
            }
        }
        return $ids;
    }
}
