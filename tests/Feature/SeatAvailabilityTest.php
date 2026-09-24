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
 * Disponibilité des places sous charge.
 *
 * Un billet `pending` correspond à un paiement en cours : la place est retenue
 * et ne doit plus être vendue. Ne compter que les billets `issued`/`used`
 * laissait passer autant d'acheteurs que de requêtes simultanées sur les
 * dernières places — donc de la survente, découverte au contrôle d'accès.
 */
class SeatAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function ticketTypeWithSeats(int $seats): TicketType
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Sold out', 'slug' => 'sold-out-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            'is_active' => true,
        ]);

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDays(10)->setTime(20, 0),
            'ends_at' => now()->addDays(10)->setTime(23, 0),
            'status' => 'active',
        ]);

        return TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => $seats,
        ]);
    }

    private function makeTicket(TicketType $type, string $status): Ticket
    {
        $order = Order::create([
            'organizer_id' => $type->event->organizer_id, 'buyer_id' => null,
            'currency' => 'XAF', 'subtotal_amount' => 1000, 'fees_amount' => 0,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 1000,
            'status' => $status === 'pending' ? 'pending' : 'paid',
            'reference' => 'ORD-' . uniqid(), 'placed_at' => now(), 'is_guest_order' => true,
        ]);

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $type->event_id,
            'ticket_type_id' => $type->id, 'code' => 'T-' . uniqid(), 'status' => $status,
        ]);
    }

    public function test_pending_ticket_occupies_a_seat(): void
    {
        $type = $this->ticketTypeWithSeats(2);
        $this->makeTicket($type, 'pending');

        $type = $type->fresh();

        $this->assertSame(1, $type->reserved_quantity, 'le paiement en cours retient une place');
        $this->assertSame(0, $type->sold_quantity, 'rien n\'est encore vendu');
        $this->assertSame(1, $type->occupied_quantity);
        $this->assertSame(1, $type->remaining_quantity, 'il ne reste qu\'une place à vendre');
    }

    public function test_issued_and_pending_tickets_fill_the_room(): void
    {
        $type = $this->ticketTypeWithSeats(2);
        $this->makeTicket($type, 'issued');
        $this->makeTicket($type, 'pending');

        $type = $type->fresh();

        $this->assertSame(2, $type->occupied_quantity);
        $this->assertSame(0, $type->remaining_quantity);
        $this->assertFalse($type->hasQuantityAvailable(1), 'plus rien à vendre');
    }

    public function test_voided_ticket_releases_its_seat(): void
    {
        $type = $this->ticketTypeWithSeats(1);
        $this->makeTicket($type, 'void');

        $type = $type->fresh();

        $this->assertSame(0, $type->occupied_quantity, 'une commande abandonnée relâche la place');
        $this->assertSame(1, $type->remaining_quantity);
        $this->assertTrue($type->hasQuantityAvailable(1));
    }

    public function test_guest_checkout_refuses_a_seat_held_by_a_pending_payment(): void
    {
        $type = $this->ticketTypeWithSeats(1);
        $this->makeTicket($type, 'pending');

        $response = $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => 'Test Acheteur',
            'guest_email' => 'acheteur@example.test',
            'guest_phone' => '+24177000000',
            'payment_method' => 'airtel',
        ]);

        $response->assertStatus(400);

        // « Seulement 0 billets disponibles » se lisait mal : quand il ne reste
        // rien, on le dit simplement, avec un code que l'écran reconnaît.
        $response->assertJsonPath('error_code', 'SOLD_OUT');
        $this->assertStringContainsString('complète', $response->json('message'));
    }

    public function test_guest_checkout_accepts_when_a_seat_is_free(): void
    {
        $type = $this->ticketTypeWithSeats(2);
        $this->makeTicket($type, 'pending');

        $response = $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => 'Test Acheteur',
            'guest_email' => 'acheteur@example.test',
            'guest_phone' => '+24177000000',
            'payment_method' => 'airtel',
        ]);

        $this->assertNotSame(400, $response->status(), 'la place restante doit rester vendable');
    }

    /**
     * Vieillit une réservation au-delà du délai de rétention.
     */
    private function expire(Ticket $ticket): void
    {
        $past = now()->subMinutes(Order::HOLD_MINUTES + 5);

        $ticket->order->forceFill(['created_at' => $past])->save();
        $ticket->forceFill(['created_at' => $past])->save();
    }

    public function test_an_expired_hold_releases_its_seat(): void
    {
        // Une commande en attente depuis plus d'une heure ne sera pas payée.
        // Tant qu'on la comptait, la place restait bloquée pour de bon dès que
        // le ménage tardait : l'événement s'affichait complet alors qu'il
        // restait des places — constaté en production sur les 4 dernières.
        $type = $this->ticketTypeWithSeats(2);
        $this->expire($this->makeTicket($type, 'pending'));

        $type = $type->fresh();

        $this->assertSame(0, $type->reserved_quantity, 'une réservation périmée ne retient plus rien');
        $this->assertSame(2, $type->remaining_quantity);
        $this->assertTrue($type->hasQuantityAvailable(2));
    }

    public function test_a_recent_hold_still_holds_its_seat(): void
    {
        // Le pendant du test précédent : le paiement en cours reste protégé,
        // sinon on rouvre la survente qu'on venait de fermer.
        $type = $this->ticketTypeWithSeats(2);
        $this->makeTicket($type, 'pending');

        $this->assertSame(1, $type->fresh()->reserved_quantity);
    }

    public function test_the_public_listing_agrees_with_the_checkout(): void
    {
        // L'affichage et la caisse doivent dire la même chose : annoncer
        // « complet » sur une place que la caisse accepterait de vendre est
        // exactement le bug remonté.
        $type = $this->ticketTypeWithSeats(1);
        $this->expire($this->makeTicket($type, 'pending'));

        $listing = $this->getJson('/api/client/events');
        $listing->assertOk();

        $event = collect($listing->json('events'))
            ->firstWhere('slug', $type->event->slug);

        $this->assertNotNull($event, 'l\'événement doit être listé');
        $this->assertFalse((bool) ($event['is_sold_out'] ?? false), 'il reste une place à vendre');
    }

    public function test_guest_checkout_accepts_a_seat_held_by_an_expired_payment(): void
    {
        $type = $this->ticketTypeWithSeats(1);
        $this->expire($this->makeTicket($type, 'pending'));

        $response = $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => 'Test Acheteur',
            'guest_email' => '{{EMAIL_002}}',
            'guest_phone' => '{{PHONE_006}}',
            'payment_method' => 'airtel',
        ]);

        $this->assertNotSame('SOLD_OUT', $response->json('error_code'),
            'la dernière place ne doit pas rester bloquée par une commande morte');
    }
}
