<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Récupération d'un billet perdu.
 *
 * Le client arrive avec son nom et un numéro de téléphone — pas forcément celui
 * de son compte : souvent celui avec lequel il a payé. Les deux doivent ouvrir
 * droit au billet. Et on ne ressort que les billets encore utiles : événement
 * actif, date pas dépassée de plus d'un jour.
 */
class TicketRetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function makeTicket(array $options = []): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert', 'slug' => 'concert-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        // `is_active` n'est pas mass-assignable sur Event : on le pose directement.
        $event->forceFill(['is_active' => $options['event_active'] ?? true])->save();

        $startsAt = $options['starts_at'] ?? now()->addDays(10);
        $schedule = EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHours(3),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Std', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id,
            'buyer_id' => $options['buyer_id'] ?? null,
            'currency' => 'XAF', 'subtotal_amount' => 1000, 'fees_amount' => 0,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 1000,
            'status' => 'paid', 'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => ! isset($options['buyer_id']),
            'guest_name' => $options['guest_name'] ?? null,
            'guest_email' => 'invite@example.test',
            'guest_phone' => $options['guest_phone'] ?? null,
        ]);

        if (isset($options['payer_phone'])) {
            Payment::create([
                'order_id' => $order->id, 'provider' => 'airtel',
                'provider_txn_ref' => 'TXN-' . uniqid(), 'amount' => 1000,
                'status' => 'success', 'payer_phone' => $options['payer_phone'],
            ]);
        }

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'schedule_id' => $schedule->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => 'issued', 'issued_at' => now(),
        ]);
    }

    private function search(array $params)
    {
        return $this->getJson('/api/v1/guest/tickets/search?' . http_build_query($params));
    }

    public function test_found_with_the_phone_used_to_pay(): void
    {
        $ticket = $this->makeTicket([
            'guest_name' => 'Leofa Abila',
            'guest_phone' => null,
            'payer_phone' => '24177443638',
        ]);

        $this->search(['name' => 'Leofa Abila', 'phone' => '077443638'])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }

    public function test_found_with_the_account_phone(): void
    {
        $type = UserType::firstOrCreate(['code' => 'client'], ['name' => 'client', 'label' => 'Client']);
        $user = User::create([
            'name' => 'Leofa Abila', 'email' => 'leofa@example.test',
            'password' => bcrypt('secret'), 'phone' => '+241 66 12 34 56', 'status' => 'active',
        ]);
        $user->user_type_id = $type->id;
        $user->save();

        $ticket = $this->makeTicket(['buyer_id' => $user->id, 'payer_phone' => '24177000000']);

        $this->search(['name' => 'Abila', 'phone' => '66123456'])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }

    public function test_name_words_match_in_any_order(): void
    {
        $ticket = $this->makeTicket(['guest_name' => 'Leofa Abila', 'guest_phone' => '077443638']);

        $this->search(['name' => 'Abila Leofa', 'phone' => '077443638'])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }

    public function test_another_name_on_the_same_phone_finds_nothing(): void
    {
        $this->makeTicket(['guest_name' => 'Leofa Abila', 'guest_phone' => '077443638']);

        $this->search(['name' => 'Jean Dupont', 'phone' => '077443638'])->assertStatus(404);
    }

    public function test_name_alone_is_refused(): void
    {
        $this->makeTicket(['guest_name' => 'Leofa Abila', 'guest_phone' => '077443638']);

        $this->search(['name' => 'Leofa Abila'])->assertStatus(422);
    }

    public function test_ticket_is_still_available_the_day_after_the_event(): void
    {
        $ticket = $this->makeTicket([
            'guest_name' => 'Leofa Abila', 'guest_phone' => '077443638',
            'starts_at' => now()->subHours(20),
        ]);

        $this->search(['name' => 'Leofa', 'phone' => '077443638'])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }

    public function test_ticket_of_a_long_past_event_is_not_returned(): void
    {
        $this->makeTicket([
            'guest_name' => 'Leofa Abila', 'guest_phone' => '077443638',
            'starts_at' => now()->subDays(5),
        ]);

        $this->search(['name' => 'Leofa', 'phone' => '077443638'])->assertStatus(404);
    }

    public function test_postponing_the_event_makes_the_ticket_available_again(): void
    {
        $ticket = $this->makeTicket([
            'guest_name' => 'Leofa Abila', 'guest_phone' => '077443638',
            'starts_at' => now()->subDays(5),
        ]);

        $this->search(['name' => 'Leofa', 'phone' => '077443638'])->assertStatus(404);

        // L'organisateur reporte : la fenêtre est recalculée à chaque recherche.
        $ticket->schedule->update([
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(3),
        ]);

        $this->search(['name' => 'Leofa', 'phone' => '077443638'])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }

    public function test_ticket_of_an_inactive_event_is_not_returned(): void
    {
        $this->makeTicket([
            'guest_name' => 'Leofa Abila', 'guest_phone' => '077443638',
            'event_active' => false,
        ]);

        $this->search(['name' => 'Leofa', 'phone' => '077443638'])->assertStatus(404);
    }

    public function test_reference_lookup_still_works(): void
    {
        $ticket = $this->makeTicket(['guest_name' => 'Leofa Abila', 'guest_phone' => '077443638']);

        $this->search(['reference' => $ticket->code])
            ->assertOk()
            ->assertJsonPath('data.tickets.0.code', $ticket->code);
    }
}
