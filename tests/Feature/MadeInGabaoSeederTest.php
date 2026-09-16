<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use Database\Seeders\MadeInGabaoEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MadeInGabaoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_event_organizer_and_tickets(): void
    {
        $this->seed(MadeInGabaoEventSeeder::class);

        // Organisateur
        $organizer = Organizer::where('slug', 'cod-on')->first();
        $this->assertNotNull($organizer);
        $this->assertSame("Cod'On", $organizer->name);

        // Événement
        $event = Event::where('slug', 'made-in-gabao-session-28')->first();
        $this->assertNotNull($event);
        $this->assertSame('MADE IN GABAO', $event->title);
        $this->assertSame('customer', $event->service_fee_bearer);
        $this->assertTrue($event->canSellTickets());
        $this->assertTrue((bool) $event->is_active);

        // Lieu
        $this->assertSame('Institut Français', DB::table('venues')->where('id', $event->venue_id)->value('name'));

        // Horaire : 17/10/2026 09h-12h
        $schedule = DB::table('event_schedules')->where('event_id', $event->id)->first();
        $this->assertNotNull($schedule);
        $this->assertStringContainsString('2026-10-17 09:00', (string) $schedule->starts_at);
        $this->assertStringContainsString('2026-10-17 12:00', (string) $schedule->ends_at);

        // Billets
        $tickets = DB::table('ticket_types')->where('event_id', $event->id)->orderBy('price')->get();
        $this->assertCount(2, $tickets);
        $this->assertSame('Standard', $tickets[0]->name);
        $this->assertEquals(200, (float) $tickets[0]->price);
        $this->assertEquals(150, (int) $tickets[0]->available_quantity);
        $this->assertSame('VIP', $tickets[1]->name);
        $this->assertEquals(500, (float) $tickets[1]->price);
        $this->assertEquals(50, (int) $tickets[1]->available_quantity);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(MadeInGabaoEventSeeder::class);
        $this->seed(MadeInGabaoEventSeeder::class);

        $this->assertSame(1, Event::where('slug', 'made-in-gabao-session-28')->count());
        $this->assertSame(1, Organizer::where('slug', 'cod-on')->count());

        $event = Event::where('slug', 'made-in-gabao-session-28')->first();
        $this->assertSame(2, DB::table('ticket_types')->where('event_id', $event->id)->count());
        $this->assertSame(1, DB::table('event_schedules')->where('event_id', $event->id)->count());
    }
}
