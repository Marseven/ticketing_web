<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Retagger en 'physical' les billets déjà importés d'événements legacy dont les
 * QR sont imprimés (ex : le stock pré-généré de l'event 156). Idempotent.
 *
 * N'affecte PAS la scannabilité (le scan valide par code) — sert au reporting
 * (dashboard « billets physiques »). Complète l'option --physical-events de
 * legacy:import pour les données déjà en base sans ré-import.
 */
class LegacyTagPhysical extends Command
{
    protected $signature = 'legacy:tag-physical
        {events : IDs d\'événements LEGACY séparés par des virgules (ex : 156)}';

    protected $description = 'Marquer physical les billets importés des événements legacy indiqués';

    public function handle(): int
    {
        $legacyIds = collect(explode(',', (string) $this->argument('events')))
            ->map(fn ($id) => (int) trim($id))->filter()->values();

        if ($legacyIds->isEmpty()) {
            $this->error('Aucun ID d\'événement legacy fourni.');
            return self::FAILURE;
        }

        $eventIds = DB::table('events')->whereIn('legacy_id', $legacyIds)->pluck('id');
        if ($eventIds->isEmpty()) {
            $this->warn('Aucun événement importé ne correspond à ces IDs legacy : ' . $legacyIds->implode(', '));
            return self::SUCCESS;
        }

        $updated = DB::table('tickets')
            ->whereIn('event_id', $eventIds)
            ->where('ticket_source', '!=', 'physical')
            ->update(['ticket_source' => 'physical', 'updated_at' => now()]);

        $this->info(sprintf(
            '%d billet(s) retaggé(s) physical sur %d événement(s) (legacy : %s).',
            $updated, $eventIds->count(), $legacyIds->implode(', ')
        ));

        return self::SUCCESS;
    }
}
