<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Organizer;
use PHPUnit\Framework\TestCase;

/**
 * Logique de commission (modèle DÉDUIT) et gating de vente.
 * Tests en mémoire — aucune dépendance BDD.
 */
class CommissionTest extends TestCase
{
    public function test_effective_commission_uses_event_override_when_set(): void
    {
        $event = new Event(['commission_percentage' => 15.5]);
        $this->assertSame(15.5, $event->effectiveCommission());
    }

    public function test_effective_commission_falls_back_to_organizer_default(): void
    {
        $organizer = new Organizer(['default_commission_percentage' => 8.0]);
        $event = new Event(['commission_percentage' => null]);
        $event->setRelation('organizer', $organizer);

        $this->assertSame(8.0, $event->effectiveCommission());
    }

    public function test_effective_commission_defaults_to_ten_percent(): void
    {
        $event = new Event(['commission_percentage' => null]);
        $event->setRelation('organizer', null);

        $this->assertSame(10.0, $event->effectiveCommission());
    }

    public function test_deduction_math_client_pays_base_organizer_gets_net(): void
    {
        // Billet 1000 XAF × 4, commission 10%
        $base = 1000 * 4;
        $commissionPct = 10.0;

        $commissionAmount = round($base * $commissionPct / 100, 2);
        $total = $base;                          // le client paie le prix affiché
        $net = round($base - $commissionAmount, 2); // net organisateur

        $this->assertSame(400.0, $commissionAmount);
        $this->assertSame(4000, $total);
        $this->assertSame(3600.0, $net);
    }

    public function test_can_sell_tickets_requires_published_and_approved(): void
    {
        $published_approved = new Event(['status' => 'published', 'approval_status' => 'approved']);
        $this->assertTrue($published_approved->canSellTickets());

        $published_pending = new Event(['status' => 'published', 'approval_status' => 'pending']);
        $this->assertFalse($published_pending->canSellTickets());

        $draft_approved = new Event(['status' => 'draft', 'approval_status' => 'approved']);
        $this->assertFalse($draft_approved->canSellTickets());

        $published_rejected = new Event(['status' => 'published', 'approval_status' => 'rejected']);
        $this->assertFalse($published_rejected->canSellTickets());
    }
}
