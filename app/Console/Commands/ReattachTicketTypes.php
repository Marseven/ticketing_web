<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rattache les billets dont la catégorie a disparu.
 *
 * La mise à jour d'un événement supprimait ses catégories puis les recréait :
 * les billets déjà vendus gardaient un `ticket_type_id` pointant vers une
 * ligne effacée. La catégorie et le tarif disparaissaient du billet — un
 * billet payé 1 000 F s'affichait « Gratuit » — et la page de récupération
 * tombait en erreur.
 *
 * La cause est corrigée ; cette commande remet d'aplomb les billets déjà dans
 * cet état. Elle ne touche qu'aux événements n'ayant qu'une seule catégorie
 * active : au-delà, on ne peut pas deviner laquelle a été achetée, et
 * attribuer un tarif au hasard sur un billet payé serait pire que de ne rien
 * faire.
 */
class ReattachTicketTypes extends Command
{
    protected $signature = 'tickets:reattach-types
        {--dry-run : lister sans rien modifier}';

    protected $description = 'Rattache les billets dont la catégorie a été supprimée à celle de leur événement';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $orphans = Ticket::query()
            ->whereNotNull('ticket_type_id')
            ->whereDoesntHave('ticketType')
            ->with('event.ticketTypes')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Aucun billet sans catégorie.');

            return self::SUCCESS;
        }

        $this->info("{$orphans->count()} billet(s) rattaché(s) à une catégorie disparue.");
        $this->newLine();

        $repaired = 0;
        $ambiguous = 0;

        foreach ($orphans->groupBy('event_id') as $eventId => $tickets) {
            $event = $tickets->first()->event;
            $types = $event?->ticketTypes->where('status', 'active')->values() ?? collect();

            if ($types->count() !== 1) {
                $this->line("  <fg=yellow>?</> {$event?->title} : {$tickets->count()} billet(s), "
                    . $types->count() . ' catégorie(s) active(s) — impossible de trancher, laissés tels quels');
                $ambiguous += $tickets->count();

                continue;
            }

            $type = $types->first();
            $this->line("  <fg=green>✓</> {$event->title} : {$tickets->count()} billet(s) → « {$type->name} » à "
                . (int) $type->price . ' ' . ($type->currency ?: 'XAF'));

            if (! $dryRun) {
                DB::table('tickets')
                    ->whereIn('id', $tickets->pluck('id'))
                    ->update(['ticket_type_id' => $type->id]);

                Log::info('Billets rattachés à une catégorie', [
                    'event_id' => $eventId,
                    'ticket_type_id' => $type->id,
                    'tickets' => $tickets->count(),
                ]);
            }

            $repaired += $tickets->count();
        }

        $this->newLine();
        $this->info(($dryRun ? '[simulation] ' : '') . "Rattachés : {$repaired} · Laissés tels quels : {$ambiguous}");

        if ($ambiguous > 0) {
            $this->line('Les billets laissés tels quels restent valables : leur prix est lu sur la '
                . 'ligne de commande, qui garde le montant réellement payé.');
        }

        return self::SUCCESS;
    }
}
