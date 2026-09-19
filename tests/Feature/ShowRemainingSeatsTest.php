<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Affichage des places restantes : exposé dans l'API publique de l'événement
 * pour que le front décide de l'afficher ou non. Masqué (false) par défaut.
 */
class ShowRemainingSeatsTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(bool $show): Event
    {
        $organizer = Organizer::create(['name' => 'Org', 'slug' => 'o-' . uniqid(), 'status' => 'active', 'is_active' => true]);

        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'E', 'slug' => 'e-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved', 'is_active' => true,
            'show_remaining_seats' => $show,
        ]);
    }

    public function test_default_is_hidden(): void
    {
        $event = $this->publishedEvent(false);
        $this->assertFalse($event->fresh()->show_remaining_seats);

        $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('event.show_remaining_seats', false);
    }

    public function test_can_be_enabled_and_is_exposed(): void
    {
        $event = $this->publishedEvent(true);

        $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('event.show_remaining_seats', true);
    }
}
