<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Organizer;
use App\Models\TicketType;
use App\Services\EBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

/**
 * Vérification périodique des paiements en attente.
 *
 * La notification d'e-billing se perd parfois : le client est débité, rien
 * n'arrive chez nous, et son billet n'est jamais émis — c'est arrivé en
 * production avec Moov. Plutôt que d'attendre, on demande l'état de la facture
 * et on encaisse par le même chemin que le webhook.
 */
class CheckPendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingPayment(int $ageMinutes = 10): Payment
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
            'subtotal_amount' => 900, 'fees_amount' => 100, 'commission_percentage' => 10,
            'tax_amount' => 0, 'total_amount' => 1026, 'status' => 'pending',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client', 'guest_email' => 'c@example.test',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
            'unit_price' => 1000, 'qty' => 2, 'line_total' => 2000,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()), 'amount' => 1026,
            'status' => 'initiated', 'billing_id' => 'BILL-' . uniqid(),
        ]);

        // Assez ancien pour que la notification normale ait eu le temps d'arriver.
        $payment->forceFill(['created_at' => now()->subMinutes($ageMinutes)])->save();

        return $payment;
    }

    private function gatewayReturns(?string $state): void
    {
        $mock = Mockery::mock(EBillingService::class);
        $mock->shouldReceive('getBillStatus')->andReturn(['success' => true, 'bill_status' => $state]);
        $this->app->instance(EBillingService::class, $mock);
    }

    public function test_a_payment_the_gateway_confirms_is_cashed_and_issues_tickets(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $payment->refresh();
        $this->assertSame('success', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('paid', $payment->order->fresh()->status);
        $this->assertSame(2, $payment->order->tickets()->where('status', 'issued')->count());
    }

    public function test_a_bill_still_unpaid_is_left_waiting(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $this->gatewayReturns('unpaid');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status);
        $this->assertSame(0, $payment->order->tickets()->count());
    }

    public function test_a_dead_bill_is_marked_failed_without_cancelling_the_order(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $this->gatewayReturns('expired');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('failed', $payment->fresh()->status);
        // La commande reste en attente : l'expiration relâchera la place, et le
        // client garde la possibilité de refaire un paiement.
        $this->assertSame('pending', $payment->order->fresh()->status);
    }

    public function test_a_very_recent_payment_is_left_to_the_webhook(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: 0);
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status, 'la notification a encore sa chance');
    }

    public function test_dry_run_changes_nothing(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame(0, $payment->order->tickets()->count());
    }

    public function test_a_gateway_that_cannot_answer_leaves_everything_alone(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();

        $mock = Mockery::mock(EBillingService::class);
        $mock->shouldReceive('getBillStatus')->andThrow(new \RuntimeException('timeout'));
        $this->app->instance(EBillingService::class, $mock);

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_running_twice_does_not_duplicate_tickets(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending')->assertSuccessful();
        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame(2, $payment->order->tickets()->count(), 'toujours 2 billets');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_a_cancelled_order_is_left_out_of_the_routine_pass(): void
    {
        // Le passage automatique reste prudent : il n'encaisse que ce qui est
        // encore en cours. Sans option, rien ne bouge sur une commande close.
        Notification::fake();
        $payment = $this->makePendingPayment();
        $payment->order->update(['status' => 'cancelled']);
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
        $this->assertSame('cancelled', $payment->order->fresh()->status);
    }

    public function test_a_bill_paid_after_cancellation_is_reported_never_cashed(): void
    {
        // L'angle mort découvert le 24/09/2026 : la commande annulée au bout
        // d'une heure sortait du champ de la vérification. Le client pouvait
        // payer ensuite — débité, sans billet, et plus personne pour poser la
        // question à e-billing. On interroge donc aussi les commandes closes.
        //
        // Mais on n'encaisse pas pour autant : la place a pu être revendue
        // entre-temps, et émettre un billet ici survendrait la salle. On
        // signale, un humain tranche entre billet et remboursement.
        Notification::fake();
        $payment = $this->makePendingPayment();
        $payment->order->update(['status' => 'cancelled']);
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending --include-closed')
            ->expectsOutputToContain('à traiter à la main')
            ->assertSuccessful();

        $payment->refresh();
        $this->assertSame('initiated', $payment->status, 'le paiement n\'est pas encaissé tout seul');
        $this->assertSame('cancelled', $payment->order->fresh()->status);
        $this->assertSame(0, $payment->order->tickets()->count(), 'aucun billet émis sur une place peut-être revendue');
    }

    public function test_a_dead_bill_on_a_cancelled_order_stops_raising_the_alarm(): void
    {
        // Le cas le plus courant : la commande a expiré, la facture aussi.
        // Le marquer `failed` éteint le voyant sans rien inventer.
        Notification::fake();
        $payment = $this->makePendingPayment();
        $payment->order->update(['status' => 'cancelled']);
        $this->gatewayReturns('expired');

        $this->artisan('payments:check-pending --include-closed')->assertSuccessful();

        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_the_dry_run_touches_nothing_on_a_cancelled_order(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment();
        $payment->order->update(['status' => 'cancelled']);
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending --include-closed --dry-run')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_a_payment_without_a_bill_is_closed_with_its_order(): void
    {
        // La facture n'a jamais été créée chez la passerelle : il n'y a rien à
        // interroger, ni maintenant ni jamais. Laissé tel quel, ce paiement
        // alimentait une alerte « état inconnu » qu'aucune commande ne pouvait
        // lever — trois cas de ce type en production.
        Notification::fake();
        $payment = $this->makePendingPayment();
        $payment->forceFill(['billing_id' => null, 'transaction_id' => null, 'payload' => null])->save();
        $payment->order->update(['status' => 'cancelled']);

        $this->artisan('payments:check-pending --include-closed')->assertSuccessful();

        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_a_payment_without_a_bill_fails_on_its_own_after_the_hold(): void
    {
        // Sans facture chez la passerelle, il n'y a rien à interroger : passé
        // le délai de rétention, inutile d'attendre que la commande soit
        // annulée pour le reconnaître.
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: \App\Models\Order::HOLD_MINUTES + 5);
        $payment->forceFill(['billing_id' => null, 'transaction_id' => null, 'payload' => null])->save();

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('pending', $payment->order->fresh()->status, 'la commande garde son sort propre');
    }

    public function test_a_payment_without_a_bill_keeps_its_chance_within_the_hold(): void
    {
        // Dans la première heure, une seconde tentative peut encore créer la
        // facture : rien ne justifie de clore le paiement.
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: 10);
        $payment->forceFill(['billing_id' => null, 'transaction_id' => null, 'payload' => null])->save();

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_an_unpaid_bill_fails_once_the_hold_has_passed(): void
    {
        // Le client n'a pas payé et sa commande est annulée : laisser le
        // paiement « en attente » donnait une liste qui ne se vidait jamais —
        // 16 lignes bloquées dans le tableau de bord de production.
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: \App\Models\Order::HOLD_MINUTES + 5);
        $this->gatewayReturns('unpaid');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('unpaid', $payment->fresh()->ebilling_state);
    }

    public function test_an_unpaid_bill_keeps_waiting_within_the_hold(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: 10);
        $this->gatewayReturns('unpaid');

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status, 'le client peut encore payer');
    }

    public function test_a_silent_gateway_never_concludes_a_failure(): void
    {
        // Le piège du 24/09/2026 : la passerelle répondait sans qu'on sache la
        // lire, et 16 factures ont paru non réglées alors qu'aucune n'avait été
        // lue. Un silence ne vaut pas un refus, même passé le délai.
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: \App\Models\Order::HOLD_MINUTES + 5);
        $this->gatewayReturns(null);

        $this->artisan('payments:check-pending')->assertSuccessful();

        $this->assertSame('initiated', $payment->fresh()->status);
    }

    public function test_a_closed_payment_is_still_watched_while_its_bill_can_be_paid(): void
    {
        // e-billing n'offre aucun moyen de fermer une facture : celle d'une
        // commande annulée reste payable. On continue donc de l'interroger,
        // sans quoi un règlement tardif serait invisible.
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: \App\Models\Order::HOLD_MINUTES + 5);
        $payment->forceFill(['status' => 'failed', 'ebilling_state' => 'unpaid'])->save();
        $payment->order->update(['status' => 'cancelled']);
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending --include-closed')
            ->expectsOutputToContain('à traiter à la main')
            ->assertSuccessful();

        $this->assertSame(0, $payment->order->tickets()->count(), 'toujours pas d\'émission automatique');
    }

    public function test_a_definitively_dead_bill_is_no_longer_watched(): void
    {
        Notification::fake();
        $payment = $this->makePendingPayment(ageMinutes: \App\Models\Order::HOLD_MINUTES + 5);
        $payment->forceFill(['status' => 'failed', 'ebilling_state' => 'expired'])->save();
        $this->gatewayReturns('processed');

        $this->artisan('payments:check-pending --include-closed')->assertSuccessful();

        $this->assertSame('expired', $payment->fresh()->ebilling_state, 'plus rien à demander sur cette facture');
    }
}
