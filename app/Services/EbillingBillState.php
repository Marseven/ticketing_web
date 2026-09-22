<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * État d'une facture e-billing, vu depuis un paiement.
 *
 * Deux usages : la vérification périodique des paiements en attente, et la
 * réconciliation des commandes créditées à tort. Les deux doivent lire la même
 * vérité — et surtout le même vocabulaire d'états, sans quoi l'une créditerait
 * ce que l'autre annule.
 */
class EbillingBillState
{
    /** États d'une facture qui valent paiement encaissé. */
    public const PAID = ['processed', 'paid'];

    /** États qui disent que la facture ne sera pas réglée. */
    public const DEAD = ['failed', 'error', 'declined', 'cancelled', 'canceled', 'expired'];

    public function __construct(private EBillingService $ebilling)
    {
    }

    /**
     * État de la facture, ou null si rien ne permet de conclure — auquel cas
     * l'appelant ne doit toucher à rien.
     *
     * @param  bool  $preferStored  utiliser l'état déjà enregistré s'il existe.
     *                              Vrai pour la réconciliation du passé ; faux
     *                              pour une vérification en direct, qui veut
     *                              l'état courant et non celui d'hier.
     */
    public function for(Payment $payment, bool $preferStored = false): ?string
    {
        if ($preferStored && is_string($payment->ebilling_state) && $payment->ebilling_state !== '') {
            return strtolower(trim($payment->ebilling_state));
        }

        $billId = $this->billId($payment);

        if (! $billId) {
            return null;
        }

        try {
            $result = $this->ebilling->getBillStatus($billId);
            $state = $result['bill_status'] ?? null;

            return is_string($state) && $state !== '' ? strtolower(trim($state)) : null;
        } catch (\Throwable $e) {
            Log::warning('E-Billing injoignable', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function isPaid(?string $state): bool
    {
        return $state !== null && in_array($state, self::PAID, true);
    }

    public function isDead(?string $state): bool
    {
        return $state !== null && in_array($state, self::DEAD, true);
    }

    /**
     * Identifiant de facture, où qu'il ait été rangé : colonne dédiée, ancien
     * champ `transaction_id`, ou payload (création de facture / webhook).
     */
    public function billId(Payment $payment): ?string
    {
        $payload = is_array($payment->payload) ? $payment->payload : [];

        $candidates = [
            $payment->billing_id,
            $payload['ebilling_bill_id'] ?? null,
            $payload['webhook_data']['billingid'] ?? null,
            $payment->transaction_id,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }

            if (is_int($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }
}
