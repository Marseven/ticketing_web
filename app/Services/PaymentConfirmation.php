<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Notifications\PaymentSuccessful;
use App\Notifications\TicketsReady;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Encaissement d'un paiement confirmé.
 *
 * Un paiement peut être confirmé de deux façons : la passerelle nous notifie
 * (webhook), ou c'est nous qui allons lui demander (vérification périodique,
 * quand sa notification s'est perdue). Les deux chemins doivent aboutir
 * exactement au même résultat — d'où ce service unique : sans lui, un billet
 * émis par relance différerait un jour d'un billet émis par webhook.
 */
class PaymentConfirmation
{
    public function __construct(private TicketIssuer $issuer)
    {
    }

    /**
     * Marque le paiement encaissé, la commande payée, et émet les billets.
     *
     * Idempotent : rejouer un webhook ou croiser une vérification périodique ne
     * crédite pas deux fois et ne duplique aucun billet.
     *
     * @param  array  $extra  colonnes supplémentaires à poser sur le paiement
     *                       (identifiants e-billing, données du webhook…)
     * @return bool  true si c'est cet appel qui a encaissé
     */
    public function confirm(Payment $payment, array $extra = []): bool
    {
        if ($payment->status === 'success' && $payment->paid_at !== null) {
            return false; // déjà encaissé
        }

        return DB::transaction(function () use ($payment, $extra) {
            $payment->update(array_merge($extra, [
                'status' => 'success',
                'paid_at' => $payment->paid_at ?? now(),
            ]));

            $order = Order::find($payment->order_id);

            if (! $order) {
                Log::error('Paiement confirmé sans commande', ['payment_id' => $payment->id]);

                return false;
            }

            $alreadyPaid = in_array($order->status, ['paid', 'completed'], true);

            if (! $alreadyPaid) {
                $order->update(['status' => 'paid', 'paid_at' => now()]);
            }

            // Les billets naissent ici. Le service est lui-même idempotent.
            $this->issuer->issue($order->fresh());

            if (! $alreadyPaid) {
                $this->notifyBuyer($order, $payment);
            }

            Log::info('Paiement encaissé et billets émis', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'reference' => $order->reference,
            ]);

            return true;
        });
    }

    /**
     * Le client est prévenu une seule fois, et jamais au prix d'un encaissement :
     * un envoi qui échoue ne doit pas annuler le paiement.
     */
    private function notifyBuyer(Order $order, Payment $payment): void
    {
        try {
            $buyer = $order->buyer;

            if (! $buyer) {
                return;
            }

            $buyer->notify(new PaymentSuccessful($payment));
            $buyer->notify(new TicketsReady($order));
        } catch (\Throwable $e) {
            Log::error('Notifications de paiement non envoyées', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
