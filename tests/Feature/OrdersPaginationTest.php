<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pagination des listes « espace client » (Mes tickets / Mes achats) et
 * « espace organisateur » (achats d'un événement).
 *
 * Ces listes s'appuient sur une pagination serveur : la page demandée, les
 * filtres et la recherche sont envoyés à l'API, et les statistiques affichées
 * portent sur l'ensemble filtré (pas seulement sur la page courante).
 */
class OrdersPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Organizer $organizer;
    private Event $event;
    private EventSchedule $schedule;
    private TicketType $ticketType;

    protected function setUp(): void
    {
        parent::setUp();

        // /api/v1/orders génère un QR PNG par billet : sans l'extension imagick
        // (absente en local) la génération lève une exception. On la neutralise
        // pour tester la pagination, pas la librairie de QR codes.
        $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::shouldReceive('format')
            ->andReturnSelf()
            ->getMock();
        $qr->shouldReceive('size')->andReturnSelf();
        $qr->shouldReceive('margin')->andReturnSelf();
        $qr->shouldReceive('generate')->andReturn('faux-png');

        $this->organizer = Organizer::create([
            'name' => 'Org Pagination',
            'slug' => 'org-pagination-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Concert Pagination',
            'slug' => 'concert-pagination-' . uniqid(),
            'description' => 'x',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
        ]);

        $this->schedule = EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDays(10)->setTime(20, 0),
            'ends_at' => now()->addDays(10)->setTime(23, 0),
            'status' => 'active',
        ]);

        $this->ticketType = TicketType::create([
            'event_id' => $this->event->id,
            'name' => 'Standard',
            'price' => 1000,
            'currency' => 'XAF',
            'status' => 'active',
        ]);
    }

    private function buyer(): User
    {
        return User::create([
            'name' => 'Client Test',
            'email' => 'client-' . uniqid() . '@example.test',
            'password' => 'secret-password',
            'status' => 'active',
        ]);
    }

    /**
     * Crée une commande (avec un billet) pour l'acheteur donné.
     */
    private function makeOrder(User $buyer, string $status = 'paid', ?string $reference = null): Order
    {
        $order = Order::create([
            'organizer_id' => $this->organizer->id,
            'buyer_id' => $buyer->id,
            'currency' => 'XAF',
            'subtotal_amount' => 900,
            'fees_amount' => 100,
            'commission_percentage' => 10,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => $status,
            'reference' => $reference ?? ('ORD-' . uniqid()),
            'placed_at' => now(),
            'is_guest_order' => false,
        ]);

        Ticket::create([
            'order_id' => $order->id,
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->ticketType->id,
            'schedule_id' => $this->schedule->id,
            'buyer_id' => $buyer->id,
            'code' => 'TKT-' . uniqid(),
            'status' => 'issued',
        ]);

        return $order;
    }

    public function test_orders_endpoint_returns_a_paginated_page_and_global_stats(): void
    {
        $buyer = $this->buyer();

        foreach (range(1, 25) as $i) {
            $this->makeOrder($buyer);
        }

        Sanctum::actingAs($buyer);

        $response = $this->getJson('/api/v1/orders?per_page=10')->assertOk();

        // La page ne contient que 10 achats...
        $this->assertCount(10, $response->json('orders'));

        // ...mais la pagination et les statistiques portent sur les 25
        $response->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 3)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 25)
            ->assertJsonPath('stats.total_orders', 25)
            ->assertJsonPath('stats.confirmed_orders', 25)
            ->assertJsonPath('stats.total_tickets', 25)
            ->assertJsonPath('stats.active_tickets', 25)
            ->assertJsonPath('stats.expired_tickets', 0)
            ->assertJsonPath('stats.upcoming_events', 1);
    }

    public function test_second_page_returns_other_orders(): void
    {
        $buyer = $this->buyer();

        foreach (range(1, 25) as $i) {
            $this->makeOrder($buyer);
        }

        Sanctum::actingAs($buyer);

        $first = collect($this->getJson('/api/v1/orders?per_page=10&page=1')->json('orders'))
            ->pluck('id');
        $second = $this->getJson('/api/v1/orders?per_page=10&page=2')->assertOk()
            ->assertJsonPath('pagination.current_page', 2);

        $secondIds = collect($second->json('orders'))->pluck('id');

        $this->assertCount(10, $secondIds);
        $this->assertEmpty($first->intersect($secondIds), 'Les pages 1 et 2 ne doivent pas se recouvrir.');
    }

    public function test_search_is_applied_server_side_on_the_whole_set(): void
    {
        $buyer = $this->buyer();

        foreach (range(1, 25) as $i) {
            $this->makeOrder($buyer);
        }

        // Une commande identifiable, volontairement créée en dernier : sans
        // recherche serveur elle serait bien au-delà de la première page.
        $needle = $this->makeOrder($buyer, 'paid', 'ORD-AIGUILLE-42');

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/orders?per_page=10&search=AIGUILLE')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.last_page', 1)
            ->assertJsonPath('stats.total_orders', 1)
            ->assertJsonPath('orders.0.order_number', $needle->reference);
    }

    public function test_search_also_matches_the_event_title(): void
    {
        $buyer = $this->buyer();
        $this->makeOrder($buyer);

        Sanctum::actingAs($buyer);

        $this->getJson('/api/v1/orders?search=Concert Pagination')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);

        $this->getJson('/api/v1/orders?search=introuvable')
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);
    }

    public function test_paid_filter_also_covers_completed_orders(): void
    {
        $buyer = $this->buyer();
        $this->makeOrder($buyer, 'paid');
        $this->makeOrder($buyer, 'completed');
        $this->makeOrder($buyer, 'pending');

        Sanctum::actingAs($buyer);

        // 'completed' est déjà renvoyé sous le libellé 'paid' par l'API :
        // le filtre doit donc couvrir les deux.
        $this->getJson('/api/v1/orders?status=paid')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);

        $this->getJson('/api/v1/orders?status=pending')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1);
    }

    public function test_organizer_event_orders_endpoint_is_paginated(): void
    {
        $buyer = $this->buyer();

        foreach (range(1, 23) as $i) {
            $this->makeOrder($buyer);
        }

        $organizerUser = User::create([
            'name' => 'Organisateur Test',
            'email' => 'orga-' . uniqid() . '@example.test',
            'password' => 'secret-password',
            'is_organizer' => true,
            'status' => 'active',
        ]);
        $organizerUser->organizers()->attach($this->organizer->id, ['role' => 'owner']);

        Sanctum::actingAs($organizerUser);

        $response = $this->getJson("/api/v1/organizer/events/{$this->event->id}/orders")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.total', 23)
            ->assertJsonPath('data.pagination.last_page', 3);

        $this->assertCount(10, $response->json('data.orders'));
        $this->assertSame(1, $response->json('data.orders.0.ticket_quantity'));

        // Dernière page : 3 achats restants
        $last = $this->getJson("/api/v1/organizer/events/{$this->event->id}/orders?page=3")
            ->assertOk()
            ->assertJsonPath('data.pagination.current_page', 3);

        $this->assertCount(3, $last->json('data.orders'));
    }

    public function test_organizer_cannot_read_orders_of_another_organizer_event(): void
    {
        $stranger = User::create([
            'name' => 'Autre Organisateur',
            'email' => 'autre-' . uniqid() . '@example.test',
            'password' => 'secret-password',
            'is_organizer' => true,
            'status' => 'active',
        ]);

        $otherOrg = Organizer::create([
            'name' => 'Autre Org',
            'slug' => 'autre-org-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
        $stranger->organizers()->attach($otherOrg->id, ['role' => 'owner']);

        Sanctum::actingAs($stranger);

        $this->getJson("/api/v1/organizer/events/{$this->event->id}/orders")
            ->assertNotFound();
    }
}
