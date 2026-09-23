<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Organizer;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ouverture de la billetterie à une date annoncée.
 *
 * L'affiche circule avant l'ouverture des ventes : l'événement doit être
 * visible sur la plateforme, avec son compte à rebours, sans que personne
 * puisse acheter avant l'heure. La visibilité et la vente sont donc deux
 * choses distinctes.
 */
class SalesOpeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(?string $salesStartAt): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            'sales_start_at' => $salesStartAt,
        ]);

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHours(4),
            'status' => 'active',
        ]);

        TicketType::create([
            'event_id' => $event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 100,
        ]);

        return $event->fresh(['ticketTypes', 'schedules']);
    }

    private function order(Event $event)
    {
        return $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $event->slug,
            'ticket_type_id' => $event->ticketTypes->first()->id,
            'quantity' => 1,
            'guest_name' => 'Client Test',
            'guest_email' => 'client@example.test',
        ]);
    }

    public function test_an_event_awaiting_its_opening_stays_visible(): void
    {
        $event = $this->makeEvent(now()->addDays(7)->toDateTimeString());

        $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('event.title', 'Chill Expo');
    }

    public function test_the_opening_date_is_published_so_the_page_can_count_down(): void
    {
        $opening = now()->addDays(7)->startOfHour();
        $event = $this->makeEvent($opening->toDateTimeString());

        $exposed = $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->json('event.sales_start_at');

        $this->assertNotNull($exposed);
        $this->assertSame($opening->toIso8601String(), $exposed);
    }

    public function test_the_listing_shows_the_event_and_its_opening_date(): void
    {
        // La carte d'un événement affiche le compte à rebours : il lui faut la
        // date, sans avoir à ouvrir la fiche.
        $opening = now()->addDays(7)->startOfHour();
        $event = $this->makeEvent($opening->toDateTimeString());

        $listed = collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('slug', $event->slug);

        $this->assertNotNull($listed, 'un événement en attente d\'ouverture reste listé');
        $this->assertSame($opening->toIso8601String(), $listed['sales_start_at']);
    }

    public function test_an_event_selling_already_carries_no_opening_date(): void
    {
        // Sans date, rien à décompter : la carte affiche le bouton d'achat.
        $event = $this->makeEvent(null);

        $listed = collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('slug', $event->slug);

        $this->assertNotNull($listed);
        $this->assertNull($listed['sales_start_at']);
    }

    public function test_an_opening_already_passed_does_not_hold_back_the_sale(): void
    {
        // Le navigateur compare la date à son horloge : une date dépassée
        // doit revenir telle quelle, et non disparaître, sinon l'écran ne
        // peut pas distinguer « ouvert » de « jamais programmé ».
        $opening = now()->subHour()->startOfHour();
        $event = $this->makeEvent($opening->toDateTimeString());

        $listed = collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('slug', $event->slug);

        $this->assertSame($opening->toIso8601String(), $listed['sales_start_at']);
        $this->assertTrue($event->salesOpen());
    }

    public function test_buying_before_the_opening_is_refused(): void
    {
        $event = $this->makeEvent(now()->addDays(7)->toDateTimeString());

        $this->order($event)
            ->assertStatus(400)
            ->assertJsonPath('error_code', 'SALES_NOT_OPEN');

        // Rien n'a été créé : ni commande, ni ligne de commande, ni billet.
        $this->assertSame(0, \App\Models\Order::count());
        $this->assertSame(0, \App\Models\OrderItem::count());
        $this->assertSame(0, \App\Models\Ticket::count());
    }

    public function test_buying_once_the_opening_has_passed_works(): void
    {
        $event = $this->makeEvent(now()->subMinute()->toDateTimeString());

        $this->order($event)->assertStatus(201);
    }

    public function test_an_event_without_an_opening_date_sells_as_before(): void
    {
        $event = $this->makeEvent(null);

        $this->assertTrue($event->salesOpen());
        $this->order($event)->assertStatus(201);
    }

    public function test_the_model_tells_when_the_opening_is_still_ahead(): void
    {
        $ahead = $this->makeEvent(now()->addHour()->toDateTimeString());
        $passed = $this->makeEvent(now()->subHour()->toDateTimeString());

        $this->assertFalse($ahead->salesOpen());
        $this->assertNotNull($ahead->salesOpeningAt());

        $this->assertTrue($passed->salesOpen());
        $this->assertNull($passed->salesOpeningAt(), 'plus de compte à rebours une fois ouvert');
    }
}
