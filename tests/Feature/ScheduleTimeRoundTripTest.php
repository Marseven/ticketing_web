<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un horaire ne doit pas bouger entre la saisie et la relecture.
 *
 * Eloquent convertit les dates en UTC pour produire du JSON : une séance
 * saisie à 09:00 heure de Libreville ressortait « 2026-12-19T08:00:00Z ». Les
 * formulaires remplissent leurs champs `datetime-local` en découpant les seize
 * premiers caractères de cette chaîne — ils affichaient 08:00, et
 * l'enregistrement stockait 08:00. À chaque passage dans le formulaire,
 * l'horaire reculait d'une heure de plus.
 *
 * Le décalage est maintenant explicite dans la chaîne, donc l'heure qu'elle
 * porte est celle qui a été saisie.
 */
class ScheduleTimeRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private function event(): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Nuit des Champions', 'slug' => 'nuit-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
            'sales_start_at' => '2026-09-23 13:00:00',
        ]);
    }

    public function test_a_schedule_keeps_the_hour_it_was_given(): void
    {
        $schedule = EventSchedule::create([
            'event_id' => $this->event()->id,
            'starts_at' => '2026-12-19 09:00:00',
            'ends_at' => '2026-12-19 20:00:00',
            'status' => 'active',
        ]);

        $json = $schedule->fresh()->toArray();

        // Ce que découpe le formulaire : les seize premiers caractères.
        $this->assertSame('2026-12-19T09:00', substr($json['starts_at'], 0, 16));
        $this->assertSame('2026-12-19T20:00', substr($json['ends_at'], 0, 16));
    }

    public function test_the_offset_is_stated_rather_than_implied(): void
    {
        $schedule = EventSchedule::create([
            'event_id' => $this->event()->id,
            'starts_at' => '2026-12-19 09:00:00',
            'ends_at' => '2026-12-19 20:00:00',
            'status' => 'active',
        ]);

        // Le décalage rend la chaîne lisible sans connaître le fuseau du
        // serveur, et `new Date()` l'interprète correctement.
        $this->assertSame('2026-12-19T09:00:00+01:00', $schedule->fresh()->toArray()['starts_at']);
    }

    public function test_the_sales_opening_keeps_its_hour_too(): void
    {
        $this->assertSame(
            '2026-09-23T13:00',
            substr($this->event()->fresh()->toArray()['sales_start_at'], 0, 16)
        );
    }

    public function test_a_banner_keeps_its_hours(): void
    {
        $banner = Banner::create([
            'title' => 'Promo', 'image_path' => 'banners/promo.jpg',
            'start_date' => '2026-10-01 08:30:00',
            'end_date' => '2026-10-31 23:00:00', 'is_active' => true,
        ]);

        $json = $banner->fresh()->toArray();

        $this->assertSame('2026-10-01T08:30', substr($json['start_date'], 0, 16));
        $this->assertSame('2026-10-31T23:00', substr($json['end_date'], 0, 16));
    }

    public function test_saving_the_same_value_back_does_not_move_it(): void
    {
        // Le scénario réel : ouvrir le formulaire, ne rien changer,
        // enregistrer. L'horaire doit être identique.
        $schedule = EventSchedule::create([
            'event_id' => $this->event()->id,
            'starts_at' => '2026-12-19 09:00:00',
            'ends_at' => '2026-12-19 20:00:00',
            'status' => 'active',
        ]);

        for ($pass = 1; $pass <= 3; $pass++) {
            $shownByTheForm = substr($schedule->fresh()->toArray()['starts_at'], 0, 16);
            $schedule->update(['starts_at' => $shownByTheForm]);
        }

        $this->assertSame('2026-12-19 09:00:00', $schedule->fresh()->starts_at->toDateTimeString());
    }
}
