<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rattache les billets dont la séance a disparu.
 *
 * La mise à jour d'un événement supprimait ses séances puis les recréait : les
 * billets déjà vendus gardaient un `schedule_id` pointant vers une ligne
 * effacée. Ils devenaient introuvables à la récupération, et la page annonçait
 * un événement passé alors qu'il avait lieu le lendemain.
 *
 * La cause est corrigée ; cette commande remet d'aplomb les billets déjà dans
 * cet état. Elle ne touche qu'aux billets dont l'événement n'a qu'une seule
 * séance : au-delà, on ne peut pas deviner laquelle était la bonne, et
 * inventer une date sur un billet vendu serait pire que de ne rien faire.
 */
class ReattachTicketSchedules extends Command
{
    protected $signature = 'tickets:reattach-schedules
        {--dry-run : lister sans rien modifier}';

    protected $description = 'Rattache les billets dont la séance a été supprimée à celle de leur événement';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $orphans = Ticket::query()
            ->whereNotNull('schedule_id')
            ->whereDoesntHave('schedule')
            ->with('event.schedules')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Aucun billet orphelin.');

            return self::SUCCESS;
        }

        $this->info("{$orphans->count()} billet(s) rattaché(s) à une séance disparue.");
        $this->newLine();

        $repaired = 0;
        $ambiguous = 0;

        foreach ($orphans->groupBy('event_id') as $eventId => $tickets) {
            $event = $tickets->first()->event;
            $schedules = $event?->schedules ?? collect();

            if ($schedules->count() !== 1) {
                $this->line("  <fg=yellow>?</> {$event?->title} : {$tickets->count()} billet(s), "
                    . $schedules->count() . ' séance(s) — impossible de trancher, laissés tels quels');
                $ambiguous += $tickets->count();

                continue;
            }

            $schedule = $schedules->first();
            $this->line("  <fg=green>✓</> {$event->title} : {$tickets->count()} billet(s) → séance du "
                . $schedule->starts_at?->format('d/m/Y H:i'));

            if (! $dryRun) {
                DB::table('tickets')
                    ->whereIn('id', $tickets->pluck('id'))
                    ->update(['schedule_id' => $schedule->id]);

                Log::info('Billets rattachés à une séance', [
                    'event_id' => $eventId,
                    'schedule_id' => $schedule->id,
                    'tickets' => $tickets->count(),
                ]);
            }

            $repaired += $tickets->count();
        }

        $this->newLine();
        $this->info(($dryRun ? '[simulation] ' : '') . "Rattachés : {$repaired} · Laissés tels quels : {$ambiguous}");

        if ($ambiguous > 0) {
            $this->line('Les billets laissés tels quels restent récupérables : la recherche se '
                . 'rabat sur les dates de leur événement.');
        }

        return self::SUCCESS;
    }
}
