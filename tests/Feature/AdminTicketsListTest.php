<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Liste admin de TOUS les billets : filtres (événement, statut, provenance)
 * et statut live. Teste directement la méthode du contrôleur (la logique de
 * requête), indépendamment de l'auth admin.
 */
class AdminTicketsListTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(string $title = 'Concert'): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => $title, 'slug' => 'ev-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
    }

    private function payload(array $params): array
    {
        $res = (new AdminController())->tickets(new Request($params));
        return json_decode($res->getContent(), true);
    }

    public function test_lists_all_tickets_with_stats(): void
    {
        $event = $this->makeEvent();
        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'VIP', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active',
        ]);
        Ticket::create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'code' => 'ON-1', 'status' => 'issued', 'ticket_source' => 'online', 'issued_at' => now()]);
        Ticket::create(['event_id' => $event->id, 'code' => 'PH-1', 'status' => 'used', 'ticket_source' => 'physical', 'issued_at' => now(), 'used_at' => now()]);

        $data = $this->payload([]);

        $this->assertTrue($data['success']);
        $this->assertSame(2, $data['data']['stats']['total']);
        $this->assertSame(1, $data['data']['stats']['issued']);
        $this->assertSame(1, $data['data']['stats']['used']);
        $this->assertSame(1, $data['data']['stats']['physical']);
        $this->assertSame(1, $data['data']['stats']['online']);
        $this->assertCount(2, $data['data']['tickets']['data']);
    }

    public function test_filters_by_status_source_and_event(): void
    {
        $event = $this->makeEvent('Alpha');
        $other = $this->makeEvent('Beta');
        Ticket::create(['event_id' => $event->id, 'code' => 'A-ON', 'status' => 'issued', 'ticket_source' => 'online', 'issued_at' => now()]);
        Ticket::create(['event_id' => $event->id, 'code' => 'A-PH', 'status' => 'used', 'ticket_source' => 'physical', 'issued_at' => now(), 'used_at' => now()]);
        Ticket::create(['event_id' => $other->id, 'code' => 'B-ON', 'status' => 'issued', 'ticket_source' => 'online', 'issued_at' => now()]);

        // Filtre statut
        $used = $this->payload(['status' => 'used']);
        $this->assertCount(1, $used['data']['tickets']['data']);
        $this->assertSame('A-PH', $used['data']['tickets']['data'][0]['code']);

        // Filtre provenance
        $physical = $this->payload(['ticket_source' => 'physical']);
        $this->assertCount(1, $physical['data']['tickets']['data']);

        // Filtre événement
        $beta = $this->payload(['event_id' => $other->id]);
        $this->assertCount(1, $beta['data']['tickets']['data']);
        $this->assertSame('B-ON', $beta['data']['tickets']['data'][0]['code']);

        // Recherche par code
        $search = $this->payload(['search' => 'A-PH']);
        $this->assertCount(1, $search['data']['tickets']['data']);
    }
}
