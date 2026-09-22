<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use App\Models\Venue;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ce que l'agent de contrôle voit après un scan.
 *
 * Le popup de l'app mobile est la seule chose que la personne à l'entrée
 * regarde avant de laisser entrer : il doit porter de quoi trancher sans
 * rouvrir un autre écran. Catégorie, tarif payé, référence de commande, lieu
 * et horaire font partie du minimum.
 *
 * Le tarif vient de la ligne de commande et non du catalogue : avec la
 * tarification variable, un billet de prévente à 2 000 F ne doit pas s'afficher
 * au prix du jour.
 */
class ScanTicketPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(array $options = []): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $venue = Venue::create([
            'organizer_id' => $organizer->id,
            'name' => 'Institut Français', 'address' => 'Boulevard Triomphal',
            'city' => 'Libreville',
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id, 'venue_id' => $venue->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);

        $schedule = EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => '2026-10-03 18:00:00', 'ends_at' => '2026-10-03 23:00:00',
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'VIP', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 2000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 2000, 'status' => 'paid',
            'reference' => 'ORD-DEMO-1', 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Mazzarine Obame',
            'guest_email' => 'mazzarine@example.test',
        ]);

        if ($options['with_line'] ?? true) {
            OrderItem::create([
                'order_id' => $order->id, 'event_id' => $event->id,
                'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
                // Prix de prévente, volontairement différent du catalogue.
                'unit_price' => 2000, 'qty' => 1, 'line_total' => 2000,
            ]);
        }

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
            'buyer_id' => null, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => 'issued', 'issued_at' => now(),
            'ticket_source' => $options['source'] ?? 'online',
        ]);
    }

    private function scanner(): User
    {
        $type = UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);

        $user = User::create([
            'name' => 'Agent', 'email' => 'agent-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = $type->id;
        $user->save();

        $role = \App\Models\Role::firstOrCreate(
            ['name' => \App\Models\Role::ADMIN],
            ['slug' => \App\Models\Role::ADMIN, 'label' => 'Administrateur', 'description' => 'Accès complet']
        );
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh('roles');
    }

    private function validate(Ticket $ticket): array
    {
        return app(TicketValidationService::class)->validate($ticket->code, [
            'scanned_by' => $this->scanner()->id,
            'enforce_schedule' => false,
        ]);
    }

    public function test_a_valid_scan_carries_everything_the_agent_needs(): void
    {
        $result = $this->validate($this->ticket());

        $this->assertSame('valid', $result['result']);

        $payload = $result['ticket'];

        $this->assertSame('Chill Expo', $payload['event']['title']);
        $this->assertSame('Institut Français', $payload['event']['venue_name']);
        $this->assertSame('VIP', $payload['ticket_type']['name']);
        $this->assertSame('ORD-DEMO-1', $payload['order']['reference']);
        $this->assertSame('Mazzarine Obame', $payload['holder']['name']);
        $this->assertSame('03/10/2026 18:00:00', $payload['schedule']['starts_at']);
        $this->assertNotNull($payload['used_at'], 'l\'heure de validation doit revenir au scan');
    }

    public function test_the_price_shown_is_the_one_actually_paid(): void
    {
        $payload = $this->validate($this->ticket())['ticket'];

        // Catalogue à 5 000, payé 2 000 en prévente : c'est 2 000 qui compte.
        $this->assertSame(2000.0, $payload['ticket_type']['price']);
        $this->assertSame('XAF', $payload['ticket_type']['currency']);
    }

    public function test_without_an_order_line_the_catalogue_price_is_used(): void
    {
        $payload = $this->validate($this->ticket(['with_line' => false]))['ticket'];

        $this->assertSame(5000.0, $payload['ticket_type']['price']);
    }

    public function test_a_second_scan_still_describes_the_ticket(): void
    {
        $ticket = $this->ticket();
        $this->validate($ticket);

        $second = $this->validate($ticket);

        // Un billet refusé doit rester identifiable : l'agent doit pouvoir dire
        // à qui il appartient et quand il est passé.
        $this->assertSame('duplicate', $second['result']);
        $this->assertNotNull($second['ticket']);
        $this->assertSame('Mazzarine Obame', $second['ticket']['holder']['name']);
        $this->assertNotNull($second['first_scan']);
    }

    public function test_a_physical_ticket_is_described_too(): void
    {
        $payload = $this->validate($this->ticket(['source' => 'physical']))['ticket'];

        $this->assertSame('physical', $payload['source']);
        $this->assertSame('Chill Expo', $payload['event']['title']);
    }
}
