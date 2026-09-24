<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Recherche d'un billet par téléphone dans l'administration.
 *
 * Quand une personne appelle, le numéro qu'on a sous la main est souvent
 * celui qui a PAYÉ — un acheteur règle fréquemment depuis le téléphone d'un
 * proche, et ce numéro-là ne figure ni sur le compte ni forcément sur la
 * commande. Les trois doivent retrouver le billet.
 */
class AdminTicketPhoneSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $type = UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $admin->user_type_id = $type->id;
        $admin->save();

        $role = Role::firstOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Admin', 'description' => 'Administrateur']
        );
        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin->fresh('roles');
    }

    /** Crée un billet dont la commande a été réglée depuis `$payerPhone`. */
    private function ticket(string $guestPhone, ?string $payerPhone): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Nuit des Champions', 'slug' => 'nuit-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Mazzarine',
            'guest_email' => 'm@primea.test', 'guest_phone' => $guestPhone,
        ]);

        if ($payerPhone !== null) {
            Payment::create([
                'order_id' => $order->id, 'provider' => 'moov',
                'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
                'amount' => 1000, 'status' => 'success', 'payer_phone' => $payerPhone,
            ]);
        }

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'buyer_id' => null,
            'code' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
            'status' => 'issued', 'issued_at' => now(), 'ticket_source' => 'online',
        ]);
    }

    private function search(array $params): array
    {
        Sanctum::actingAs($this->admin());

        return $this->getJson('/api/v1/admin/tickets?' . http_build_query($params))
            ->assertOk()
            ->json('data.tickets.data') ?? [];
    }

    public function test_the_paying_phone_finds_the_ticket(): void
    {
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['phone' => '077222222']))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_the_order_phone_finds_it_too(): void
    {
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['phone' => '062111111']))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_the_format_does_not_matter(): void
    {
        // Les numéros sont dictés au téléphone : avec indicatif, avec espaces,
        // avec des tirets. La comparaison porte sur les chiffres.
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        foreach (['+241 77 22 22 22', '077-22-22-22', '24177222222'] as $saisie) {
            $codes = collect($this->search(['phone' => $saisie]))->pluck('code');

            $this->assertContains($ticket->code, $codes, "saisie : {$saisie}");
        }
    }

    public function test_another_number_finds_nothing(): void
    {
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['phone' => '066999999']))->pluck('code');

        $this->assertNotContains($ticket->code, $codes);
    }

    public function test_the_general_search_also_accepts_a_number(): void
    {
        // L'administrateur tape naturellement le numéro dans la recherche.
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['search' => '077222222']))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_the_general_search_still_finds_a_code(): void
    {
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['search' => $ticket->code]))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_a_guest_name_is_found(): void
    {
        // Le nom d'un acheteur invité vit sur la COMMANDE, pas sur un compte.
        // Ne chercher que dans les comptes ne trouvait presque rien.
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $codes = collect($this->search(['search' => 'Mazzarine']))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_the_words_of_a_name_may_come_in_any_order(): void
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo 1', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Roseline MYANDA',
            'guest_email' => 'roseline@primea.test', 'guest_phone' => '062333333',
        ]);

        $ticket = Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'buyer_id' => null,
            'code' => 'TKT-ROSELINE', 'status' => 'issued',
            'issued_at' => now(), 'ticket_source' => 'online',
        ]);

        // Tel que l'administrateur le tape, dans l'ordre inverse.
        $codes = collect($this->search(['search' => 'MYANDA ROSELINE']))->pluck('code');

        $this->assertContains($ticket->code, $codes);
    }

    public function test_the_row_shows_the_paying_number(): void
    {
        $this->ticket(guestPhone: '062111111', payerPhone: '077222222');

        $row = collect($this->search(['phone' => '077222222']))->first();

        // Chercher un numéro sans pouvoir le lire ensuite n'avance à rien.
        $this->assertSame('077222222', $row['payer_phone']);
        $this->assertSame('062111111', $row['holder_phone']);
    }

    public function test_a_ticket_paid_without_a_recorded_number_still_lists(): void
    {
        $ticket = $this->ticket(guestPhone: '062111111', payerPhone: null);

        $row = collect($this->search([]))->firstWhere('code', $ticket->code);

        $this->assertNotNull($row);
        $this->assertNull($row['payer_phone']);
    }
}
