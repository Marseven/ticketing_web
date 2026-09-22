<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'application vit à l'heure de Libreville.
 *
 * En UTC, une heure saisie « 18:00 » par un organisateur était stockée telle
 * quelle puis relue comme de l'UTC : le navigateur d'un visiteur gabonais
 * (UTC+1) affichait alors 19:00. Une heure d'écart invisible en interne, mais
 * bien visible sur une affiche annonçant l'ouverture d'une billetterie.
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_runs_on_libreville_time(): void
    {
        $this->assertSame('Africa/Libreville', config('app.timezone'));
        $this->assertSame('+01:00', now()->format('P'), 'le Gabon est à UTC+1, sans heure d\'été');
    }

    public function test_a_time_typed_by_an_organizer_comes_back_unchanged(): void
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            // Ce que le formulaire envoie : une heure locale, sans fuseau.
            'sales_start_at' => '2026-10-09 18:00:00',
        ]);

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => '2026-10-09 20:00:00', 'ends_at' => '2026-10-09 23:00:00',
            'status' => 'active',
        ]);

        // Stockée telle que saisie…
        $this->assertSame('2026-10-09 18:00:00', $event->fresh()->getRawOriginal('sales_start_at'));

        // …et transmise au navigateur avec le décalage du Gabon, pour qu'il
        // affiche 18:00 et non 19:00.
        $exposed = $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->json('event.sales_start_at');

        $this->assertSame('2026-10-09T18:00:00+01:00', $exposed);
    }
}
