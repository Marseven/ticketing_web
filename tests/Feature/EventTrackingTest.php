<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page publique de suivi par jeton : résumé + stats, liste des billets
 * (scannés/non), détail avec « qui a scanné ». Le jeton fait l'accès.
 */
class EventTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvent(): array
    {
        $organizer = Organizer::create([
            'name' => 'Cod\'On', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'MADE IN GABAO', 'slug' => 'ev-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'VIP', 'price' => 500,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $t1 = Ticket::create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'code' => 'TKT-ISSUED', 'status' => 'issued', 'ticket_source' => 'online', 'issued_at' => now()]);
        $t2 = Ticket::create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'code' => 'TKT-USED', 'status' => 'used', 'ticket_source' => 'physical', 'issued_at' => now(), 'used_at' => now()]);

        $scanner = User::create(['name' => 'Agent Porte A', 'email' => 'agent-' . uniqid() . '@x.test', 'password' => bcrypt('x')]);
        Checkin::create(['ticket_id' => $t2->id, 'scanned_by' => $scanner->id, 'result' => 'valid', 'scanned_at' => now()]);

        return [$event, $event->ensureTrackingToken()];
    }

    public function test_summary_returns_event_and_stats(): void
    {
        [, $token] = $this->seedEvent();

        $res = $this->getJson("/api/v1/track/{$token}");

        $res->assertOk()
            ->assertJsonPath('data.event.title', 'MADE IN GABAO')
            ->assertJsonPath('data.stats.total', 2)
            ->assertJsonPath('data.stats.scanned', 1)
            ->assertJsonPath('data.stats.not_scanned', 1)
            ->assertJsonPath('data.stats.physical', 1)
            ->assertJsonPath('data.stats.online', 1);
    }

    public function test_tickets_list_exposes_scan_state_and_scanner(): void
    {
        [, $token] = $this->seedEvent();

        $all = $this->getJson("/api/v1/track/{$token}/tickets");
        $all->assertOk();
        $this->assertCount(2, $all->json('data.tickets.data'));

        // Filtre "scanné"
        $scanned = $this->getJson("/api/v1/track/{$token}/tickets?scan=scanned")->json('data.tickets.data');
        $this->assertCount(1, $scanned);
        $this->assertSame('TKT-USED', $scanned[0]['code']);
        $this->assertTrue($scanned[0]['scanned']);
        $this->assertSame('Agent Porte A', $scanned[0]['scanned_by']);

        // Filtre "non scanné"
        $not = $this->getJson("/api/v1/track/{$token}/tickets?scan=not_scanned")->json('data.tickets.data');
        $this->assertCount(1, $not);
        $this->assertSame('TKT-ISSUED', $not[0]['code']);

        // Recherche par code
        $search = $this->getJson("/api/v1/track/{$token}/tickets?search=USED")->json('data.tickets.data');
        $this->assertCount(1, $search);
    }

    public function test_ticket_detail_includes_checkin_history(): void
    {
        [, $token] = $this->seedEvent();

        $res = $this->getJson("/api/v1/track/{$token}/tickets/TKT-USED");

        $res->assertOk()
            ->assertJsonPath('data.ticket.code', 'TKT-USED')
            ->assertJsonPath('data.ticket.checkins.0.scanned_by', 'Agent Porte A')
            ->assertJsonPath('data.ticket.checkins.0.result', 'valid');
    }

    public function test_invalid_token_is_not_found(): void
    {
        $this->seedEvent();
        $this->getJson('/api/v1/track/wrong-token-xyz')->assertNotFound();
    }

    public function test_summary_splits_counts_and_revenue_by_source(): void
    {
        [$event, $token] = $this->seedEvent();

        // Une vente en ligne payée : 2 000 de billets + 50 de frais de service.
        // Le revenu suit `subtotal_amount`, comme le tableau de bord organisateur.
        $order = \App\Models\Order::create([
            'organizer_id' => $event->organizer_id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 2000, 'fees_amount' => 0, 'service_fee_amount' => 50,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 2050,
            'status' => 'paid', 'reference' => 'ORD-' . strtoupper(uniqid()),
            'placed_at' => now(), 'is_guest_order' => true,
        ]);
        \App\Models\Ticket::where('code', 'TKT-ISSUED')->update(['order_id' => $order->id]);

        $res = $this->getJson("/api/v1/track/{$token}")->assertOk();

        $res->assertJsonPath('data.stats.online', 1)
            ->assertJsonPath('data.stats.physical', 1)
            ->assertJsonPath('data.stats.by_source.physical.scanned', 1)
            ->assertJsonPath('data.stats.by_source.online.scanned', 0);

        // En ligne : le sous-total, pas les frais. Physique : le tarif du type.
        $this->assertSame(2000.0, (float) $res->json('data.stats.revenue_online'));
        $this->assertSame(500.0, (float) $res->json('data.stats.revenue_physical'));
        $this->assertSame(2500.0, (float) $res->json('data.stats.revenue'));
    }

    public function test_comped_tickets_bring_no_revenue(): void
    {
        [$event, $token] = $this->seedEvent();

        \App\Models\Ticket::create([
            'event_id' => $event->id,
            'ticket_type_id' => $event->ticketTypes()->first()->id,
            'code' => 'TKT-INVIT', 'status' => 'issued',
            'ticket_source' => 'comped', 'issued_at' => now(),
        ]);

        $res = $this->getJson("/api/v1/track/{$token}")->assertOk();

        $res->assertJsonPath('data.stats.comped', 1)
            ->assertJsonPath('data.stats.by_source.comped.revenue', 0);
        $this->assertSame(500.0, (float) $res->json('data.stats.revenue'), 'seul le billet physique compte');
    }

    public function test_ticket_list_can_be_filtered_by_source(): void
    {
        [, $token] = $this->seedEvent();

        $online = $this->getJson("/api/v1/track/{$token}/tickets?ticket_source=online")->assertOk();
        $this->assertSame(['TKT-ISSUED'], collect($online->json('data.tickets.data'))->pluck('code')->all());

        $physical = $this->getJson("/api/v1/track/{$token}/tickets?ticket_source=physical")->assertOk();
        $this->assertSame(['TKT-USED'], collect($physical->json('data.tickets.data'))->pluck('code')->all());
    }

    public function test_unpaid_and_void_tickets_are_invisible_in_tracking(): void
    {
        [$event, $token] = $this->seedEvent();
        $type = $event->ticketTypes()->first();

        // Paiement jamais abouti, et commande annulée : ni l'un ni l'autre n'est
        // une vente. Ils gonflaient les compteurs et le revenu du suivi.
        \App\Models\Ticket::create([
            'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'code' => 'TKT-PENDING', 'status' => 'pending', 'ticket_source' => 'online',
        ]);
        \App\Models\Ticket::create([
            'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'code' => 'TKT-VOID', 'status' => 'void', 'ticket_source' => 'physical',
        ]);

        $res = $this->getJson("/api/v1/track/{$token}")->assertOk();

        // Seuls les 2 billets payés du jeu d'essai comptent.
        $res->assertJsonPath('data.stats.total', 2)
            ->assertJsonPath('data.stats.online', 1)
            ->assertJsonPath('data.stats.physical', 1);

        // Le billet physique impayé ne doit pas rapporter de revenu.
        $this->assertSame(500.0, (float) $res->json('data.stats.revenue_physical'));

        $codes = collect($this->getJson("/api/v1/track/{$token}/tickets")->json('data.tickets.data'))
            ->pluck('code');
        $this->assertFalse($codes->contains('TKT-PENDING'));
        $this->assertFalse($codes->contains('TKT-VOID'));
    }

    public function test_an_unpaid_ticket_detail_is_not_reachable(): void
    {
        [$event, $token] = $this->seedEvent();

        \App\Models\Ticket::create([
            'event_id' => $event->id,
            'ticket_type_id' => $event->ticketTypes()->first()->id,
            'code' => 'TKT-PENDING', 'status' => 'pending', 'ticket_source' => 'online',
        ]);

        $this->getJson("/api/v1/track/{$token}/tickets/TKT-PENDING")->assertStatus(404);
    }

    public function test_summary_exposes_the_commission_already_deducted(): void
    {
        [$event, $token] = $this->seedEvent();
        $event->update(['commission_percentage' => 15]);

        $this->getJson("/api/v1/track/{$token}")
            ->assertOk()
            ->assertJsonPath('data.stats.commission_percentage', 15);
    }

    public function test_commission_falls_back_to_the_organizer_default(): void
    {
        [$event, $token] = $this->seedEvent();
        $event->update(['commission_percentage' => null]);
        $event->organizer->update(['default_commission_percentage' => 12]);

        $this->getJson("/api/v1/track/{$token}")
            ->assertOk()
            ->assertJsonPath('data.stats.commission_percentage', 12);
    }
}
