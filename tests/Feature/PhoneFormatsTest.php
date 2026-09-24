<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formes d'un numéro gabonais.
 *
 * Le même numéro arrive avec l'indicatif ou sans, avec le zéro de tête ou
 * sans, avec des espaces ou des tirets. Celui qui cherche son billet tape ce
 * qu'il a sous les yeux : les huit chiffres de l'abonné sont la seule partie
 * stable, et c'est sur eux que porte la comparaison.
 */
class PhoneFormatsTest extends TestCase
{
    use RefreshDatabase;

    private function ticketWithPhone(string $stored, bool $pastEvent = false): Ticket
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'o-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'Chill Expo 1',
            'slug' => 'chill-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved',
        ]);

        $when = $pastEvent ? now()->subDays(5) : now()->addDays(5);

        EventSchedule::create([
            'event_id' => $event->id, 'starts_at' => $when,
            'ends_at' => (clone $when)->addHours(3), 'status' => 'active',
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
            'is_guest_order' => true, 'guest_name' => 'MYANDA ROSELINE',
            'guest_email' => 'guest@primea.ga', 'guest_phone' => $stored,
        ]);

        return Ticket::create([
            'order_id' => $order->id, 'event_id' => $event->id,
            'ticket_type_id' => $type->id, 'buyer_id' => null,
            'code' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
            'status' => 'issued', 'issued_at' => now(), 'ticket_source' => 'online',
        ]);
    }

    private function search(string $phone, string $name = 'Myanda roseline')
    {
        return $this->getJson('/api/v1/guest/tickets/search?' . http_build_query([
            'name' => $name, 'phone' => $phone,
        ]));
    }

    public function test_every_way_of_writing_the_number_finds_the_ticket(): void
    {
        $ticket = $this->ticketWithPhone('+24177855949');

        $formes = [
            '077855949',          // saisie nationale, avec le zéro
            '77855949',           // les huit chiffres de l'abonné
            '+24177855949',       // international
            '24177855949',        // international sans le +
            '+241 77 85 59 49',   // avec des espaces
            '077-85-59-49',       // avec des tirets
            '+241077855949',      // indicatif ET zéro, saisie fréquente
        ];

        foreach ($formes as $forme) {
            $codes = collect($this->search($forme)->json('data.tickets'))->pluck('code');

            $this->assertContains($ticket->code, $codes, "forme : {$forme}");
        }
    }

    public function test_a_number_stored_with_the_leading_zero_is_found_too(): void
    {
        // Selon le parcours, le numéro est enregistré avec ou sans indicatif.
        $ticket = $this->ticketWithPhone('077855949');

        foreach (['+24177855949', '77855949', '077855949'] as $forme) {
            $codes = collect($this->search($forme)->json('data.tickets'))->pluck('code');

            $this->assertContains($ticket->code, $codes, "forme : {$forme}");
        }
    }

    public function test_another_number_finds_nothing(): void
    {
        $this->ticketWithPhone('+24177855949');

        $this->search('066112233')->assertStatus(404);
    }

    public function test_a_past_event_says_so_instead_of_pretending_nothing_exists(): void
    {
        // « Aucun ticket trouvé » laissait croire à une erreur de saisie, et la
        // personne recommençait avec d'autres numéros alors que son billet
        // existe bel et bien.
        $this->ticketWithPhone('+24177855949', pastEvent: true);

        $response = $this->search('077855949')->assertStatus(404);

        $response->assertJsonPath('error_code', 'EVENT_OVER');
        $this->assertStringContainsString('déjà passé', $response->json('message'));
    }

    public function test_an_unknown_number_keeps_the_ordinary_message(): void
    {
        $this->ticketWithPhone('+24177855949', pastEvent: true);

        $this->search('066112233')->assertJsonPath('error_code', 'NOT_FOUND');
    }
}
