<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Services\TicketTypeSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catégories de billets et billets déjà vendus.
 *
 * La mise à jour d'un événement supprimait toutes ses catégories puis les
 * recréait. Or `tickets.ticket_type_id` pointe dessus : un billet payé
 * perdait sa catégorie et son tarif, et la page de récupération tombait en
 * erreur — « Attempt to read property "name" on null » s'est affiché à un
 * acheteur.
 */
class TicketTypeSyncTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;
    private TicketType $standard;
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'o-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Chill Expo 1',
            'slug' => 'chill-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved',
        ]);

        $this->standard = TicketType::create([
            'event_id' => $this->event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 100,
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client', 'guest_email' => 'c@primea.test',
        ]);

        $this->ticket = Ticket::create([
            'order_id' => $order->id, 'event_id' => $this->event->id,
            'ticket_type_id' => $this->standard->id, 'buyer_id' => null,
            'code' => 'TKT-VENDU', 'status' => 'issued',
            'issued_at' => now(), 'ticket_source' => 'online',
        ]);
    }

    private function sync(array $wanted): void
    {
        app(TicketTypeSync::class)->sync($this->event->fresh(), $wanted);
    }

    public function test_a_sold_ticket_keeps_its_category_after_an_edit(): void
    {
        $this->sync([
            ['id' => $this->standard->id, 'name' => 'Standard', 'price' => 1000, 'capacity' => 100],
        ]);

        $this->assertSame($this->standard->id, $this->ticket->fresh()->ticket_type_id);
        $this->assertSame('Standard', $this->ticket->fresh()->ticketType->name);
    }

    public function test_it_survives_a_form_that_sends_no_identifier(): void
    {
        // L'écran d'administration n'envoyait pas l'identifiant : le nom sert
        // alors de point d'ancrage.
        $this->sync([
            ['name' => 'Standard', 'price' => 1500, 'capacity' => 120],
        ]);

        $this->assertSame($this->standard->id, $this->ticket->fresh()->ticket_type_id);
        $this->assertSame(1500.0, (float) $this->standard->fresh()->price);
    }

    public function test_renaming_a_category_keeps_the_sold_tickets_attached(): void
    {
        $this->sync([
            ['id' => $this->standard->id, 'name' => 'Tarif unique', 'price' => 1000, 'capacity' => 100],
        ]);

        $this->assertSame($this->standard->id, $this->ticket->fresh()->ticket_type_id);
        $this->assertSame('Tarif unique', $this->ticket->fresh()->ticketType->name);
    }

    public function test_a_sold_category_removed_from_the_grid_is_deactivated_not_deleted(): void
    {
        // Effacer une ligne dont dépendent des billets payés n'est pas une
        // mise à jour, c'est une perte de données.
        $this->sync([
            ['name' => 'VIP', 'price' => 5000, 'capacity' => 20],
        ]);

        $this->assertNotNull($this->standard->fresh(), 'la catégorie vendue doit survivre');
        $this->assertSame('inactive', $this->standard->fresh()->status);
        $this->assertSame($this->standard->id, $this->ticket->fresh()->ticket_type_id);
    }

    public function test_an_unsold_category_removed_from_the_grid_is_deleted(): void
    {
        $jamaisVendue = TicketType::create([
            'event_id' => $this->event->id, 'name' => 'Loge', 'price' => 9000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 5,
        ]);

        $this->sync([
            ['id' => $this->standard->id, 'name' => 'Standard', 'price' => 1000, 'capacity' => 100],
        ]);

        $this->assertNull(TicketType::find($jamaisVendue->id));
    }

    public function test_a_new_category_is_created(): void
    {
        $this->sync([
            ['id' => $this->standard->id, 'name' => 'Standard', 'price' => 1000, 'capacity' => 100],
            ['name' => 'VIP', 'price' => 5000, 'capacity' => 20],
        ]);

        $this->assertSame(2, $this->event->ticketTypes()->where('status', 'active')->count());
    }

    public function test_the_price_paid_survives_a_lost_category(): void
    {
        // Un billet payé 1 000 F s'affichait « Gratuit » dès que sa catégorie
        // avait été effacée. Le montant est sur la ligne de commande.
        \App\Models\OrderItem::create([
            'order_id' => $this->ticket->order_id, 'event_id' => $this->event->id,
            'ticket_type_id' => $this->standard->id,
            'unit_price' => 1000, 'qty' => 1, 'line_total' => 1000,
        ]);

        $this->ticket->forceFill(['ticket_type_id' => 999999])->save();

        $this->assertSame(1000.0, $this->ticket->fresh()->price_paid);
    }

    public function test_the_price_paid_beats_a_catalogue_price_changed_since(): void
    {
        // L'organisateur a monté son tarif après la vente : le billet garde
        // le montant réellement payé.
        \App\Models\OrderItem::create([
            'order_id' => $this->ticket->order_id, 'event_id' => $this->event->id,
            'ticket_type_id' => $this->standard->id,
            'unit_price' => 1000, 'qty' => 1, 'line_total' => 1000,
        ]);

        $this->standard->update(['price' => 5000]);

        $this->assertSame(1000.0, $this->ticket->fresh()->price_paid);
    }

    public function test_the_repair_command_reattaches_an_orphaned_ticket(): void
    {
        $this->ticket->forceFill(['ticket_type_id' => 999999])->save();

        $this->artisan('tickets:reattach-types')->assertSuccessful();

        $this->assertSame($this->standard->id, $this->ticket->fresh()->ticket_type_id);
    }

    public function test_the_repair_command_leaves_ambiguous_events_alone(): void
    {
        TicketType::create([
            'event_id' => $this->event->id, 'name' => 'VIP', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 20,
        ]);

        $this->ticket->forceFill(['ticket_type_id' => 999999])->save();

        $this->artisan('tickets:reattach-types')->assertSuccessful();

        // Deux catégories : attribuer un tarif au hasard sur un billet payé
        // serait pire que de s'abstenir.
        $this->assertSame(999999, $this->ticket->fresh()->ticket_type_id);
    }

    public function test_the_search_no_longer_breaks_on_a_ticket_without_a_category(): void
    {
        // Le cas déjà présent en base : un billet dont la catégorie a été
        // effacée par une ancienne mise à jour. La page ne doit pas tomber.
        $this->ticket->forceFill(['ticket_type_id' => 999999])->save();

        $response = $this->getJson('/api/v1/guest/tickets/search?' . http_build_query([
            'reference' => 'TKT-VENDU',
        ]));

        $this->assertNotSame(500, $response->status(), 'aucune erreur technique ne doit remonter');
    }
}
