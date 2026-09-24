<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Événement complet.
 *
 * Quand toutes les places sont vendues avant la date, il faut le dire sur la
 * carte, sur la fiche et dans le tunnel d'achat — et refuser l'achat. Le
 * décompte doit être le MÊME partout : annoncer « 2 places restantes » puis
 * refuser à la caisse est pire que de ne rien annoncer.
 */
class SoldOutTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Nuit des Champions', 'slug' => 'nuit-des-champions',
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(4),
            'status' => 'active',
        ]);

        $this->type = TicketType::create([
            'event_id' => $this->event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 2,
        ]);
    }

    private function sellSeats(int $quantity): void
    {
        $order = Order::create([
            'organizer_id' => $this->event->organizer_id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000 * $quantity, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000 * $quantity, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client', 'guest_email' => 'c@primea.test',
        ]);

        for ($i = 0; $i < $quantity; $i++) {
            Ticket::create([
                'order_id' => $order->id, 'event_id' => $this->event->id,
                'ticket_type_id' => $this->type->id, 'buyer_id' => null,
                'code' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
                'status' => 'issued', 'issued_at' => now(), 'ticket_source' => 'online',
            ]);
        }
    }

    private function holdSeats(int $quantity): void
    {
        $order = Order::create([
            'organizer_id' => $this->event->organizer_id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000 * $quantity, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000 * $quantity, 'status' => 'pending',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client', 'guest_email' => 'c@primea.test',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'event_id' => $this->event->id,
            'ticket_type_id' => $this->type->id,
            'unit_price' => 1000, 'qty' => $quantity, 'line_total' => 1000 * $quantity,
        ]);
    }

    private function listed(): ?array
    {
        return collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('slug', 'nuit-des-champions');
    }

    private function detail(): array
    {
        return $this->getJson('/api/client/events/nuit-des-champions')->assertOk()->json('event');
    }

    // ---------------------------------------------------------------
    //  Ce que voient la carte et la fiche
    // ---------------------------------------------------------------

    public function test_an_event_with_seats_left_is_not_flagged(): void
    {
        $this->sellSeats(1);

        $this->assertFalse($this->listed()['is_sold_out']);
        $this->assertFalse($this->detail()['is_sold_out']);
        $this->assertSame(1, $this->listed()['ticket_types'][0]['remaining_quantity']);
    }

    public function test_selling_every_seat_flags_the_event(): void
    {
        $this->sellSeats(2);

        $this->assertTrue($this->listed()['is_sold_out']);
        $this->assertTrue($this->detail()['is_sold_out']);
        $this->assertSame(0, $this->listed()['ticket_types'][0]['remaining_quantity']);
        $this->assertTrue($this->listed()['ticket_types'][0]['is_sold_out']);
    }

    public function test_seats_held_by_a_pending_payment_count_as_taken(): void
    {
        // Sans cela, la carte annonçait des places déjà retenues, et l'achat
        // était refusé au dernier moment.
        $this->sellSeats(1);
        $this->holdSeats(1);

        $this->assertTrue($this->listed()['is_sold_out']);
        $this->assertTrue($this->detail()['is_sold_out']);
    }

    public function test_an_unlimited_category_is_never_full(): void
    {
        $this->type->update(['available_quantity' => null]);
        $this->sellSeats(50);

        $this->assertFalse($this->listed()['is_sold_out']);
        $this->assertNull($this->listed()['ticket_types'][0]['remaining_quantity']);
    }

    public function test_one_category_left_keeps_the_event_open(): void
    {
        TicketType::create([
            'event_id' => $this->event->id, 'name' => 'VIP', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 10,
        ]);

        $this->sellSeats(2); // épuise seulement « Standard »

        $listed = $this->listed();

        $this->assertFalse($listed['is_sold_out'], 'il reste du VIP');
        $this->assertTrue(collect($listed['ticket_types'])->firstWhere('name', 'Standard')['is_sold_out']);
        $this->assertFalse(collect($listed['ticket_types'])->firstWhere('name', 'VIP')['is_sold_out']);
    }

    public function test_the_organiser_choice_about_showing_seats_reaches_the_card(): void
    {
        // Le réglage n'était pas envoyé dans la liste : la carte affichait le
        // nombre de places restantes même quand l'organisateur l'avait masqué.
        $this->event->forceFill(['show_remaining_seats' => true])->save();

        $this->assertTrue($this->listed()['show_remaining_seats']);
    }

    // ---------------------------------------------------------------
    //  Ce que fait la caisse
    // ---------------------------------------------------------------

    public function test_buying_a_sold_out_category_is_refused(): void
    {
        $this->sellSeats(2);

        $this->postJson('/api/v1/guest/orders', [
            'event_slug' => 'nuit-des-champions',
            'ticket_type_id' => $this->type->id,
            'quantity' => 1,
            'guest_name' => 'Client Test',
            'guest_email' => 'test@primea.test',
            'guest_phone' => '062000000',
            'payment_method' => 'airtel',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error_code', 'SOLD_OUT');
    }

    public function test_asking_for_more_than_what_is_left_says_how_many_remain(): void
    {
        $this->sellSeats(1);

        $this->postJson('/api/v1/guest/orders', [
            'event_slug' => 'nuit-des-champions',
            'ticket_type_id' => $this->type->id,
            'quantity' => 5,
            'guest_name' => 'Client Test',
            'guest_email' => 'test@primea.test',
            'guest_phone' => '062000000',
            'payment_method' => 'airtel',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error_code', 'NOT_ENOUGH_SEATS')
            ->assertJsonPath('remaining', 1);
    }
}
