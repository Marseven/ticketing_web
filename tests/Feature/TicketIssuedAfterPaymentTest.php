<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Le billet naît du paiement, pas de la commande.
 *
 * Avant, une commande créait aussitôt ses billets en `pending` : des billets
 * sans paiement, qu'il fallait ensuite exclure des comptages et nettoyer. Une
 * commande non payée ne porte désormais qu'une ligne de commande — laquelle
 * retient tout de même la place le temps du règlement, sans quoi on
 * revendrait les dernières places pendant que le client paie.
 */
class TicketIssuedAfterPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le rappel exige désormais une source vérifiée : il refuse par défaut.
     * Ces tests portent sur ce que le rappel FAIT une fois admis ; le contrôle
     * de la source a ses propres tests (EbillingWebhookAuthTest). On ouvre donc
     * ici par l'adresse, celle d'où partent les requêtes de test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ebilling.webhook_secret' => null,
            'services.ebilling.webhook_allowed_ips' => '127.0.0.1',
        ]);
    }

    private function makeEvent(int $seats = 10, int $price = 1000): TicketType
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            'service_fee_bearer' => 'platform',
        ]);

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(7)->addHours(4),
            'status' => 'active',
        ]);

        return TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => $price,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => $seats,
        ]);
    }

    private function order(TicketType $type, int $quantity = 1)
    {
        return $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => $quantity,
            'guest_name' => 'Client Test',
            'guest_email' => 'client@example.test',
        ]);
    }

    public function test_ordering_creates_no_ticket(): void
    {
        $type = $this->makeEvent();

        $this->order($type)->assertStatus(201);

        $order = Order::latest('id')->first();
        $this->assertSame('pending', $order->status);
        $this->assertSame(0, $order->tickets()->count(), 'aucun billet avant paiement');
        $this->assertSame(1, $order->items()->count(), 'la commande garde ce qui a été demandé');
    }

    public function test_an_unpaid_order_still_holds_the_seat(): void
    {
        $type = $this->makeEvent(seats: 1);

        $this->order($type)->assertStatus(201);

        // La place est retenue le temps du règlement : personne d'autre ne
        // doit pouvoir acheter la dernière.
        $this->assertSame(0, $type->fresh()->remaining_quantity);
        $this->order($type)->assertStatus(400);
    }

    public function test_paying_creates_the_tickets(): void
    {
        Notification::fake();
        $type = $this->makeEvent();
        $this->order($type, 3)->assertStatus(201);

        $order = Order::latest('id')->first();
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $order->total_amount, 'status' => 'initiated',
        ]);

        $this->postJson('/api/v1/webhooks/ebilling', [
            'reference' => $payment->provider_txn_ref,
            'transactionid' => 'TXN-1',
            'paymentsystem' => 'moovmoney',
            'amount' => $order->total_amount,
            'state' => 'processed',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame(3, $order->tickets()->count());
        $this->assertSame(3, $order->tickets()->where('status', 'issued')->count());
        $this->assertNotEmpty($order->tickets()->first()->code);
    }

    public function test_a_replayed_webhook_does_not_duplicate_tickets(): void
    {
        Notification::fake();
        $type = $this->makeEvent();
        $this->order($type, 2)->assertStatus(201);

        $order = Order::latest('id')->first();
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $order->total_amount, 'status' => 'initiated',
        ]);

        $payload = [
            'reference' => $payment->provider_txn_ref,
            'transactionid' => 'TXN-1',
            'paymentsystem' => 'moovmoney',
            'amount' => $order->total_amount,
            'state' => 'processed',
        ];

        $this->postJson('/api/v1/webhooks/ebilling', $payload)->assertOk();
        $this->postJson('/api/v1/webhooks/ebilling', $payload)->assertOk();

        $this->assertSame(2, $order->tickets()->count(), 'toujours 2 billets, pas 4');
    }

    public function test_an_unpaid_notification_issues_nothing(): void
    {
        Notification::fake();
        $type = $this->makeEvent();
        $this->order($type)->assertStatus(201);

        $order = Order::latest('id')->first();
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $order->total_amount, 'status' => 'initiated',
        ]);

        $this->postJson('/api/v1/webhooks/ebilling', [
            'reference' => $payment->provider_txn_ref,
            'transactionid' => 'TXN-1',
            'paymentsystem' => 'moovmoney',
            'amount' => $order->total_amount,
            'state' => 'ready',
        ])->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $order->tickets()->count());
    }

    public function test_a_free_order_gets_its_tickets_straight_away(): void
    {
        Notification::fake();
        $type = $this->makeEvent(price: 0);

        $this->order($type, 2)->assertStatus(201);

        $order = Order::latest('id')->first();
        $this->assertSame('paid', $order->status, 'rien à payer');
        $this->assertSame(2, $order->tickets()->where('status', 'issued')->count());
    }

    public function test_orders_from_before_the_change_are_activated_not_duplicated(): void
    {
        Notification::fake();
        $type = $this->makeEvent();
        $this->order($type)->assertStatus(201);

        $order = Order::latest('id')->first();

        // Commande de l'ancien modèle : son billet existe déjà, en attente.
        Ticket::create([
            'order_id' => $order->id, 'event_id' => $type->event_id,
            'ticket_type_id' => $type->id, 'code' => 'TKT-LEGACY', 'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $order->total_amount, 'status' => 'initiated',
        ]);

        $this->postJson('/api/v1/webhooks/ebilling', [
            'reference' => $payment->provider_txn_ref,
            'transactionid' => 'TXN-1',
            'paymentsystem' => 'moovmoney',
            'amount' => $order->total_amount,
            'state' => 'processed',
        ])->assertOk();

        $this->assertSame(1, $order->tickets()->count(), 'le billet existant est activé, pas doublé');
        $this->assertSame('issued', $order->tickets()->first()->status);
    }
}
