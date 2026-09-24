<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketType;
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
 * cet état, dans cet ordre :
 *
 *   1. la LIGNE DE COMMANDE, quand elle existe — ce n'est pas une supposition,
 *      c'est ce que la personne a effectivement acheté ;
 *   2. à défaut, la catégorie unique de l'événement ;
 *   3. sinon on s'abstient : attribuer un tarif au hasard sur un billet payé
 *      serait pire que de ne rien faire. `--ticket` et `--type` permettent
 *      alors de trancher à la main, quand l'exploitant sait.
 */
class ReattachTicketTypes extends Command
{
    protected $signature = 'tickets:reattach-types
        {--dry-run : lister sans rien modifier}
        {--ticket= : ne traiter que ce code de billet}
        {--type= : forcer cette catégorie (identifiant), pour les cas que la commande ne peut pas trancher}';

    protected $description = 'Rattache les billets dont la catégorie a été supprimée à celle de leur événement';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $orphans = Ticket::query()
            ->whereNotNull('ticket_type_id')
            ->whereDoesntHave('ticketType')
            ->when($this->option('ticket'), fn ($q, $code) => $q->where('code', $code))
            ->with(['event.ticketTypes', 'order.items'])
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('Aucun billet sans catégorie.');

            return self::SUCCESS;
        }

        $this->info("{$orphans->count()} billet(s) rattaché(s) à une catégorie disparue.");
        $this->newLine();

        $forced = $this->forcedType();

        if ($this->option('type') && ! $forced) {
            $this->error('Catégorie introuvable : ' . $this->option('type'));

            return self::FAILURE;
        }

        $repaired = 0;
        $ambiguous = 0;

        foreach ($orphans as $ticket) {
            $type = $forced ?? $this->resolve($ticket);

            if (! $type) {
                $this->line("  <fg=yellow>?</> {$ticket->code} · {$ticket->event?->title} — "
                    . 'aucune certitude, laissé tel quel');
                $this->showCandidates($ticket);
                $ambiguous++;

                continue;
            }

            $this->line("  <fg=green>✓</> {$ticket->code} → « {$type->name} » à "
                . (int) $type->price . ' ' . ($type->currency ?: 'XAF'));

            if (! $dryRun) {
                DB::table('tickets')->where('id', $ticket->id)->update(['ticket_type_id' => $type->id]);

                Log::info('Billet rattaché à une catégorie', [
                    'ticket' => $ticket->code,
                    'ticket_type_id' => $type->id,
                    'forced' => (bool) $forced,
                ]);
            }

            $repaired++;
        }

        $this->newLine();
        $this->info(($dryRun ? '[simulation] ' : '') . "Rattachés : {$repaired} · Laissés tels quels : {$ambiguous}");

        if ($ambiguous > 0) {
            $this->newLine();
            $this->line('Pour trancher un cas à la main, quand vous savez ce qui a été acheté :');
            $this->line('  php artisan tickets:reattach-types --ticket=TKT-XXXXXXXX --type=<identifiant>');
        }

        return self::SUCCESS;
    }

    /**
     * Catégorie imposée par l'exploitant, quand la commande ne peut pas
     * trancher seule.
     */
    private function forcedType(): ?TicketType
    {
        $id = $this->option('type');

        return $id ? TicketType::find($id) : null;
    }

    /**
     * Ce que la personne a réellement acheté.
     *
     * La ligne de commande fait foi : ce n'est pas une supposition. On ne se
     * rabat sur la catégorie unique de l'événement qu'à défaut de ligne.
     */
    private function resolve(Ticket $ticket): ?TicketType
    {
        $lines = $ticket->order?->items;

        if ($lines?->count()) {
            $ids = $lines->pluck('ticket_type_id')->filter()->unique();

            // Une seule catégorie dans la commande : aucun doute possible.
            if ($ids->count() === 1) {
                $type = TicketType::find($ids->first());

                if ($type && $type->event_id === $ticket->event_id) {
                    return $type;
                }
            }
        }

        $actives = $ticket->event?->ticketTypes->where('status', 'active')->values() ?? collect();

        if ($actives->count() === 1) {
            return $actives->first();
        }

        return $this->fromAmountPaid($ticket, $actives);
    }

    /**
     * Retrouve la catégorie par le MONTANT réglé.
     *
     * Une commande d'un seul billet à 50 000 F ne peut être qu'un billet à
     * 50 000 F. Ce n'est plus une supposition dès lors qu'une seule catégorie
     * porte ce prix — si deux catégories coûtent pareil, on s'abstient, car
     * rien ne permet alors de les distinguer.
     *
     * On compare le sous-total, hors frais de service : ce sont les places
     * qu'il représente.
     *
     * @param  \Illuminate\Support\Collection<int, TicketType>  $actives
     */
    private function fromAmountPaid(Ticket $ticket, $actives): ?TicketType
    {
        $order = $ticket->order;

        if (! $order || $order->subtotal_amount === null) {
            return null;
        }

        $quantity = $order->tickets()->count();

        if ($quantity < 1) {
            return null;
        }

        $unit = round(((float) $order->subtotal_amount) / $quantity, 2);

        $matching = $actives->filter(fn (TicketType $type) => abs(((float) $type->price) - $unit) < 0.01)
            ->values();

        return $matching->count() === 1 ? $matching->first() : null;
    }

    /** Aide l'exploitant à choisir en listant ce qui existe. */
    private function showCandidates(Ticket $ticket): void
    {
        foreach ($ticket->event?->ticketTypes ?? [] as $type) {
            $this->line("        --type={$type->id} · {$type->name} · "
                . (int) $type->price . ' ' . ($type->currency ?: 'XAF'));
        }
    }
}
