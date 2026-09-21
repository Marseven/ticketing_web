<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Émission des billets d'une commande.
 *
 * Un billet n'existe qu'à partir du paiement : tant que la commande n'est pas
 * réglée, seule la ligne de commande (`order_items`) enregistre ce qui a été
 * demandé, et c'est elle qui retient la place. Créer les billets dès la
 * commande produisait des billets sans paiement — comptés comme vendus,
 * visibles dans les suivis, et à nettoyer après coup.
 */
class TicketIssuer
{
    /**
     * Crée les billets d'une commande payée.
     *
     * Idempotent : un webhook qui arrive deux fois ne duplique rien. Les
     * commandes d'avant ce changement ont déjà leurs billets en `pending` :
     * on les bascule alors en `issued` au lieu d'en créer de nouveaux.
     *
     * @return int nombre de billets désormais valides
     */
    public function issue(Order $order): int
    {
        return DB::transaction(function () use ($order) {
            $existing = $order->tickets()->get();

            if ($existing->isNotEmpty()) {
                return $this->activateExisting($order, $existing);
            }

            return $this->createFromItems($order);
        });
    }

    /**
     * Commandes passées avant le changement : les billets existent déjà.
     */
    private function activateExisting(Order $order, $tickets): int
    {
        $activated = 0;

        foreach ($tickets as $ticket) {
            if (in_array($ticket->status, ['issued', 'used'], true)) {
                continue; // déjà valide : webhook rejoué
            }

            $ticket->update(['status' => 'issued', 'issued_at' => now()]);
            $activated++;
        }

        if ($activated > 0) {
            Log::info('Billets activés pour la commande', [
                'order_id' => $order->id,
                'count' => $activated,
            ]);
        }

        return $tickets->count();
    }

    private function createFromItems(Order $order): int
    {
        $items = $order->items()->get();

        if ($items->isEmpty()) {
            Log::error('Commande payée sans ligne de commande : aucun billet à émettre', [
                'order_id' => $order->id,
                'reference' => $order->reference,
            ]);

            return 0;
        }

        $created = 0;

        foreach ($items as $item) {
            for ($i = 0; $i < $item->qty; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $item->event_id,
                    'ticket_type_id' => $item->ticket_type_id,
                    'schedule_id' => $item->schedule_id,
                    'buyer_id' => $order->buyer_id,
                    'code' => $this->uniqueCode(),
                    'status' => 'issued',
                    'issued_at' => now(),
                ]);
                $created++;
            }
        }

        Log::info('Billets émis après paiement', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'count' => $created,
        ]);

        return $created;
    }

    /**
     * Code de billet unique — celui qu'encode le QR et que lit le scanner.
     */
    private function uniqueCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (Ticket::where('code', $code)->exists());

        return $code;
    }
}
