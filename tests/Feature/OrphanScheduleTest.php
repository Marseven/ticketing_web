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
 * Billet dont la séance a été recréée.
 *
 * La mise à jour d'un événement supprimait toutes ses séances puis les
 * recréait : les billets déjà vendus gardaient alors un `schedule_id` pointant
 * vers une ligne disparue. La récupération de billet, qui cherche la séance
 * pour vérifier que l'événement n'est pas passé, ne trouvait plus rien et
 * annonçait un événement passé — alors qu'il a lieu demain.
 */
class OrphanScheduleTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;
    private Event $event;

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

        $schedule = EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDay()->setTime(17, 0),
            'ends_at' => now()->addDay()->setTime(23, 59),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $this->event->id, 'name' => 'Standard', 'price' => 1000,
            'currency' => 'XAF', 'status' => 'active',
        ]);

        $order = Order::create([
            'organizer_id' => $organizer->id, 'buyer_id' => null, 'currency' => 'XAF',
            'subtotal_amount' => 1000, 'fees_amount' => 0, 'tax_amount' => 0,
            'total_amount' => 1000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'MYANDA ROSELINE',
            'guest_email' => 'guest@primea.ga', 'guest_phone' => '+24177855949',
        ]);

        $this->ticket = Ticket::create([
            'order_id' => $order->id, 'event_id' => $this->event->id,
            'ticket_type_id' => $type->id, 'schedule_id' => $schedule->id,
            'buyer_id' => null, 'code' => 'TKT-ROSELINE',
            'status' => 'issued', 'issued_at' => now(), 'ticket_source' => 'online',
        ]);
    }

    private function search()
    {
        return $this->getJson('/api/v1/guest/tickets/search?' . http_build_query([
            'name' => 'Myanda roseline', 'phone' => '077855949',
        ]));
    }

    public function test_the_ticket_is_found_while_its_schedule_exists(): void
    {
        $codes = collect($this->search()->json('data.tickets'))->pluck('code');

        $this->assertContains('TKT-ROSELINE', $codes);
    }

    public function test_editing_the_event_keeps_the_ticket_attached_to_its_seance(): void
    {
        // Ce que fait vraiment l'administration : renvoyer les mêmes séances
        // lors d'une modification de titre. L'identifiant doit survivre.
        $avant = $this->ticket->schedule_id;

        app(\App\Services\EventScheduleSync::class)->sync($this->event, [[
            'starts_at' => now()->addDay()->setTime(17, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(23, 59)->toDateTimeString(),
        ]]);

        $this->assertSame($avant, $this->ticket->fresh()->schedule_id);
        $this->assertSame(1, $this->event->schedules()->count());
    }

    public function test_postponing_the_event_updates_the_seance_in_place(): void
    {
        // Un report doit déplacer la date SANS casser les billets vendus.
        $avant = $this->ticket->schedule_id;

        app(\App\Services\EventScheduleSync::class)->sync($this->event, [[
            'starts_at' => now()->addMonth()->setTime(20, 0)->toDateTimeString(),
            'ends_at' => now()->addMonth()->setTime(23, 59)->toDateTimeString(),
        ]]);

        $this->assertSame($avant, $this->ticket->fresh()->schedule_id);
        $this->assertTrue(
            $this->ticket->fresh()->schedule->starts_at->isNextMonth()
                || $this->ticket->fresh()->schedule->starts_at->greaterThan(now()->addWeeks(3))
        );
    }

    public function test_a_seance_really_removed_is_deleted(): void
    {
        \App\Models\EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDays(3)->setTime(17, 0),
            'ends_at' => now()->addDays(3)->setTime(23, 59),
            'status' => 'active',
        ]);

        $this->assertSame(2, $this->event->schedules()->count());

        app(\App\Services\EventScheduleSync::class)->sync($this->event, [[
            'starts_at' => now()->addDay()->setTime(17, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(23, 59)->toDateTimeString(),
        ]]);

        $this->assertSame(1, $this->event->schedules()->count());
    }

    public function test_the_repair_command_reattaches_an_orphaned_ticket(): void
    {
        $this->event->schedules()->delete();

        $nouvelle = EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDay()->setTime(17, 0),
            'ends_at' => now()->addDay()->setTime(23, 59),
            'status' => 'active',
        ]);

        $this->artisan('tickets:reattach-schedules')->assertSuccessful();

        $this->assertSame($nouvelle->id, $this->ticket->fresh()->schedule_id);
    }

    public function test_the_repair_command_leaves_ambiguous_cases_alone(): void
    {
        // Deux séances : impossible de deviner laquelle était la bonne.
        // Inventer une date sur un billet vendu serait pire que de s'abstenir.
        $ancienne = $this->ticket->schedule_id;
        $this->event->schedules()->delete();

        foreach ([1, 3] as $jours) {
            EventSchedule::create([
                'event_id' => $this->event->id,
                'starts_at' => now()->addDays($jours)->setTime(17, 0),
                'ends_at' => now()->addDays($jours)->setTime(23, 59),
                'status' => 'active',
            ]);
        }

        $this->artisan('tickets:reattach-schedules')->assertSuccessful();

        $this->assertSame($ancienne, $this->ticket->fresh()->schedule_id);

        // Et il reste récupérable malgré tout, grâce au repli sur l'événement.
        $codes = collect($this->search()->json('data.tickets'))->pluck('code');
        $this->assertContains('TKT-ROSELINE', $codes);
    }

    public function test_the_dry_run_changes_nothing(): void
    {
        $ancienne = $this->ticket->schedule_id;
        $this->event->schedules()->delete();

        EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDay()->setTime(17, 0),
            'ends_at' => now()->addDay()->setTime(23, 59),
            'status' => 'active',
        ]);

        $this->artisan('tickets:reattach-schedules', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame($ancienne, $this->ticket->fresh()->schedule_id);
    }

    public function test_it_is_still_found_after_the_schedule_was_recreated(): void
    {
        // Ce que fait la mise à jour d'un événement depuis l'administration :
        // supprimer les séances puis les recréer, avec de nouveaux
        // identifiants. Le billet garde l'ancien.
        $this->event->schedules()->delete();

        EventSchedule::create([
            'event_id' => $this->event->id,
            'starts_at' => now()->addDay()->setTime(17, 0),
            'ends_at' => now()->addDay()->setTime(23, 59),
            'status' => 'active',
        ]);

        $codes = collect($this->search()->json('data.tickets'))->pluck('code');

        $this->assertContains('TKT-ROSELINE', $codes,
            'un billet dont la séance a été recréée doit rester récupérable');
    }
}
