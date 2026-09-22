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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Validation d'une commande à la main par un administrateur.
 *
 * Deux choses se jouent ici. Le vocabulaire des statuts d'abord : l'écran
 * envoyait « confirmed », que la base ne connaît pas — le serveur répondait 422
 * et le bouton ne faisait rien.
 *
 * Et surtout : depuis que les billets ne sont créés qu'au paiement, valider une
 * commande à la main doit les émettre. Sans ça l'admin confirmerait une
 * commande payée qui ne donne aucun billet.
 */
class AdminOrderStatusTest extends TestCase
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

        // L'accès admin passe par le rôle, pas par le type d'utilisateur.
        $role = \App\Models\Role::firstOrCreate(
            ['name' => \App\Models\Role::ADMIN],
            ['slug' => \App\Models\Role::ADMIN, 'label' => 'Administrateur', 'description' => 'Accès complet']
        );
        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin->fresh('roles');
    }

    private function pendingOrder(int $qty = 2): Order
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
            'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(4),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 900 * $qty, 'fees_amount' => 100 * $qty,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => 1026 * $qty,
            'status' => 'pending', 'reference' => 'ORD-' . strtoupper(uniqid()),
            'placed_at' => now(), 'is_guest_order' => true,
            'guest_name' => 'Mazzarine', 'guest_email' => 'guest@primea.test',
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
            'unit_price' => 1000, 'qty' => $qty, 'line_total' => 1000 * $qty,
        ]);

        return $order;
    }

    private function setStatus(Order $order, string $status)
    {
        Sanctum::actingAs($this->admin());

        return $this->putJson("/api/v1/admin/orders/{$order->id}/status", ['status' => $status]);
    }

    public function test_confirming_an_order_by_hand_issues_its_tickets(): void
    {
        $order = $this->pendingOrder(qty: 2);
        $this->assertSame(0, $order->tickets()->count());

        $this->setStatus($order, 'paid')->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(2, $order->tickets()->where('status', 'issued')->count());
    }

    public function test_the_status_the_screen_used_to_send_is_still_refused(): void
    {
        // « confirmed » n'existe pas en base : l'écran envoie « paid ».
        $this->setStatus($this->pendingOrder(), 'confirmed')->assertStatus(422);
    }

    public function test_confirming_twice_does_not_duplicate_tickets(): void
    {
        $order = $this->pendingOrder(qty: 1);

        $this->setStatus($order, 'paid')->assertOk();
        $this->setStatus($order, 'paid')->assertOk();

        $this->assertSame(1, $order->tickets()->count());
    }

    public function test_cancelling_by_hand_releases_the_seats(): void
    {
        $order = $this->pendingOrder(qty: 2);
        $this->setStatus($order, 'paid')->assertOk();

        $this->setStatus($order, 'cancelled')->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(2, $order->tickets()->where('status', 'void')->count());
    }

    public function test_cancelling_leaves_a_ticket_already_scanned_alone(): void
    {
        $order = $this->pendingOrder(qty: 1);
        $this->setStatus($order, 'paid')->assertOk();

        $order->tickets()->first()->update(['status' => 'used', 'used_at' => now()]);

        $this->setStatus($order, 'cancelled')->assertOk();

        // La personne est entrée : son passage reste inscrit.
        $this->assertSame('used', $order->tickets()->first()->status);
    }
}
