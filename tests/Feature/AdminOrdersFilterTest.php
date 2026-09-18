<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Régression : le filtre « Événement » de la page Achats filtrait par une
 * colonne orders.event_id inexistante (l'événement est lié via les tickets).
 * Il doit désormais filtrer par la relation tickets sans erreur.
 */
class AdminOrdersFilterTest extends TestCase
{
    use RefreshDatabase;

    private function eventWithOrder(string $title): array
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => $title, 'slug' => 'ev-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
        $order = Order::create([
            'organizer_id' => $organizer->id,
            'currency' => 'XAF', 'subtotal_amount' => 1000, 'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Jean', 'guest_email' => 'j@x.test',
        ]);
        Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'code' => 'T-' . strtoupper(uniqid()), 'status' => 'issued',
            'ticket_source' => 'online', 'issued_at' => now(),
        ]);

        return [$event, $order];
    }

    public function test_orders_filter_by_event_uses_tickets_relation(): void
    {
        [$eventA] = $this->eventWithOrder('Alpha');
        [$eventB, $orderB] = $this->eventWithOrder('Beta');

        $res = (new AdminController())->orders(new Request(['event_id' => $eventB->id]));
        $data = json_decode($res->getContent(), true);

        $this->assertSame(200, $res->status());
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']['orders']['data']);
        $this->assertSame($orderB->reference, $data['data']['orders']['data'][0]['reference']);
    }
}
