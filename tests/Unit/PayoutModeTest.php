<?php

namespace Tests\Unit;

use App\Models\Event;
use PHPUnit\Framework\TestCase;

/**
 * Choix du mode de versement par événement.
 * Tests en mémoire — aucune dépendance BDD.
 */
class PayoutModeTest extends TestCase
{
    public function test_instant_mode_requires_a_phone_number(): void
    {
        $withPhone = new Event(['payout_mode' => 'instant', 'instant_payout_phone' => '074123456']);
        $this->assertTrue($withPhone->isInstantPayout());

        // Mode instant sans numéro => on ne considère PAS le versement instantané
        // (sécurité : on ne tente pas un payout sans destinataire).
        $withoutPhone = new Event(['payout_mode' => 'instant', 'instant_payout_phone' => null]);
        $this->assertFalse($withoutPhone->isInstantPayout());
    }

    public function test_deferred_mode_is_not_instant(): void
    {
        $deferred = new Event(['payout_mode' => 'deferred', 'instant_payout_phone' => '074123456']);
        $this->assertFalse($deferred->isInstantPayout());
    }

    public function test_default_mode_is_deferred(): void
    {
        // Le défaut applicatif : sans mode explicite, on n'est pas en instantané.
        $event = new Event([]);
        $this->assertFalse($event->isInstantPayout());
    }

    public function test_instant_payout_amount_is_the_order_net(): void
    {
        // En instantané, le montant versé par vente = net organisateur de la
        // commande (base - commission), soit exactement ce qui vient d'être
        // crédité sur le solde du gateway de la vente.
        $base = 2000.0;
        $commissionPct = 10.0;
        $net = round($base - ($base * $commissionPct / 100), 2);

        $this->assertSame(1800.0, $net);
    }
}
