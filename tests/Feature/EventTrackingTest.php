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
}
