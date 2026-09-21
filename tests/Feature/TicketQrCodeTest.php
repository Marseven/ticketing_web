<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le QR du billet doit venir de l'API.
 *
 * Sans lui, le front retombait sur api.qrserver.com : le code du billet partait
 * chez un tiers, et si ce service était injoignable le client se retrouvait
 * avec un billet — téléchargé en JPG — sans QR, donc inutilisable à l'entrée.
 */
class TicketQrCodeTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert', 'slug' => 'concert-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            'is_active' => true,
        ]);

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDays(5)->setTime(20, 0),
            'ends_at' => now()->addDays(5)->setTime(23, 0),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'commission_percentage' => 10,
            'tax_amount' => 0, 'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Invité', 'guest_email' => 'invite@example.test',
        ]);

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'code' => 'TCK-' . strtoupper(uniqid()), 'status' => 'issued', 'issued_at' => now(),
        ]);
    }

    public function test_ticket_endpoint_returns_a_self_hosted_qr_image(): void
    {
        $ticket = $this->makeTicket();

        $qr = $this->getJson("/api/v1/tickets/{$ticket->code}")
            ->assertOk()
            ->json('ticket.qr_code');

        $this->assertIsString($qr);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qr);
        $this->assertStringNotContainsString('qrserver.com', $qr);

        $svg = base64_decode(substr($qr, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_guest_search_returns_a_self_hosted_qr_image(): void
    {
        $ticket = $this->makeTicket();

        // Recherche invité : la réponse est enveloppée dans `data`.
        $qr = $this->getJson('/api/v1/guest/tickets/search?reference=' . $ticket->order->reference)
            ->assertOk()
            ->json('data.tickets.0.qr_code');

        $this->assertIsString($qr);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qr);
    }
}
