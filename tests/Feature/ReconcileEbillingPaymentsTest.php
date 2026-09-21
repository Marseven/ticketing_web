<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\EBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Réconciliation des commandes créditées à tort.
 *
 * Le webhook e-billing prenait toute notification pour un paiement abouti :
 * des commandes ont été marquées payées sans que le client valide. Cette
 * commande interroge la passerelle et annule celles qui n'ont jamais été
 * réglées — sans jamais toucher à ce qu'elle n'a pas pu vérifier.
 */
class ReconcileEbillingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidOrder(?string $billingId = 'BILL-1', string $ticketStatus = 'issued'): Payment
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
            'subtotal_amount' => 900, 'fees_amount' => 100, 'service_fee_amount' => 26,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 1026,
            'status' => 'paid', 'reference' => 'ORD-' . strtoupper(uniqid()),
            'placed_at' => now(), 'processed_at' => now(), 'is_guest_order' => true,
            'guest_name' => 'Client', 'guest_email' => 'c@example.test',
        ]);

        Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'schedule_id' => $schedule->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => $ticketStatus, 'issued_at' => now(),
            'used_at' => $ticketStatus === 'used' ? now() : null,
        ]);

        return Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()), 'amount' => 1026,
            'status' => 'success', 'paid_at' => now(), 'billing_id' => $billingId,
        ]);
    }

    private function gatewayReturns(?string $state): void
    {
        $mock = Mockery::mock(EBillingService::class);
        $mock->shouldReceive('getBillStatus')->andReturn(['success' => true, 'bill_status' => $state]);
        $this->app->instance(EBillingService::class, $mock);
    }

    public function test_an_unpaid_order_is_cancelled_and_its_seats_released(): void
    {
        $payment = $this->makePaidOrder();
        $this->gatewayReturns('ready');

        $this->artisan('payments:reconcile-ebilling')->assertSuccessful();

        $this->assertSame('cancelled', $payment->order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('void', $payment->order->tickets()->first()->status);
    }

    public function test_a_genuinely_paid_order_is_left_alone(): void
    {
        $payment = $this->makePaidOrder();
        $this->gatewayReturns('processed');

        $this->artisan('payments:reconcile-ebilling')->assertSuccessful();

        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('issued', $payment->order->tickets()->first()->status);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $payment = $this->makePaidOrder();
        $this->gatewayReturns('ready');

        $this->artisan('payments:reconcile-ebilling', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertSame('issued', $payment->order->tickets()->first()->status);
    }

    public function test_an_order_the_gateway_cannot_confirm_is_never_touched(): void
    {
        // Pas d'identifiant de facture : impossible de trancher.
        $payment = $this->makePaidOrder(null);
        $this->gatewayReturns('ready');

        $this->artisan('payments:reconcile-ebilling')->assertSuccessful();

        $this->assertSame('paid', $payment->order->fresh()->status);
    }

    public function test_a_gateway_failure_leaves_the_order_alone(): void
    {
        $payment = $this->makePaidOrder();

        $mock = Mockery::mock(EBillingService::class);
        $mock->shouldReceive('getBillStatus')->andThrow(new \RuntimeException('timeout'));
        $this->app->instance(EBillingService::class, $mock);

        $this->artisan('payments:reconcile-ebilling')->assertSuccessful();

        $this->assertSame('paid', $payment->order->fresh()->status);
    }

    public function test_a_ticket_already_scanned_is_not_voided(): void
    {
        // La personne est entrée : on annule la commande mais on ne réécrit pas
        // l'histoire du contrôle d'accès.
        $payment = $this->makePaidOrder('BILL-1', 'used');
        $this->gatewayReturns('ready');

        $this->artisan('payments:reconcile-ebilling')->assertSuccessful();

        $this->assertSame('cancelled', $payment->order->fresh()->status);
        $this->assertSame('used', $payment->order->tickets()->first()->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
