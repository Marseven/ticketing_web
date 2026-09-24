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
 * Webhook e-billing : recevoir une notification ne vaut pas paiement.
 *
 * La passerelle notifie aussi à la création de la facture et en cas d'échec.
 * Le code créditait la commande dès réception, quel que soit l'état : un client
 * qui n'avait rien validé arrivait sur « Paiement réussi ! » avec un billet à
 * l'écran. Seuls les états « processed » et « paid » valent encaissement —
 * vocabulaire repris de l'intégration en production.
 */
class EbillingWebhookTest extends TestCase
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

    private function makePayment(): Payment
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        $schedule = EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHours(4),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 25, 'commission_percentage' => 10,
            'tax_amount' => 0, 'total_amount' => 1025, 'status' => 'pending',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client Test',
            'guest_email' => 'client@example.test', 'guest_phone' => '077443638',
        ]);

        Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'schedule_id' => $schedule->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => 'pending',
        ]);

        return Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()), 'amount' => 1025,
            'status' => 'initiated',
        ]);
    }

    private function notify(Payment $payment, array $extra = [])
    {
        return $this->postJson('/api/v1/webhooks/ebilling', array_merge([
            'reference' => $payment->provider_txn_ref,
            'transactionid' => 'TXN-' . uniqid(),
            'paymentsystem' => 'moovmoney',
            'amount' => 1025,
        ], $extra));
    }

    public function test_a_notification_without_payment_leaves_the_order_pending(): void
    {
        Notification::fake();
        $payment = $this->makePayment();

        // « ready » : la facture existe, le client n'a rien validé.
        $this->notify($payment, ['state' => 'ready'])->assertOk();

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status);
        $this->assertSame('pending', $payment->order->tickets()->first()->status);
    }

    public function test_a_failed_state_marks_the_payment_failed(): void
    {
        Notification::fake();
        $payment = $this->makePayment();

        $this->notify($payment, ['state' => 'failed'])->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertNotSame('paid', $payment->order->fresh()->status);
    }

    public function test_a_processed_state_credits_the_order_and_issues_the_ticket(): void
    {
        Notification::fake();
        $payment = $this->makePayment();

        $this->notify($payment, ['state' => 'processed'])->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertSame('issued', $payment->order->tickets()->first()->status);
    }

    public function test_paid_state_is_accepted_too(): void
    {
        Notification::fake();
        $payment = $this->makePayment();

        $this->notify($payment, ['state' => 'PAID'])->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
    }

    public function test_a_notification_without_state_does_not_credit_on_its_own(): void
    {
        Notification::fake();
        $payment = $this->makePayment();

        // Sans état et sans identifiant de facture, rien ne permet de conclure :
        // on laisse la commande en attente plutôt que d'offrir un billet.
        $this->notify($payment)->assertOk();

        $this->assertNotSame('success', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status);
    }

    public function test_an_unpaid_state_never_credits_the_order(): void
    {
        // « unpaid » est l'état qu'e-billing a renvoyé sur les commandes que
        // l'ancien webhook créditait quand même.
        Notification::fake();
        $payment = $this->makePayment();

        $this->notify($payment, ['state' => 'unpaid'])->assertOk();

        $this->assertNotSame('success', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status);
        $this->assertSame('pending', $payment->order->tickets()->first()->status);
    }
}
