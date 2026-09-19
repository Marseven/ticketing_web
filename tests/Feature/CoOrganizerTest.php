<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use Database\Seeders\BantuFashionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Co-organisateur (2e organisateur) : affichage « Organisée par X et Y ».
 * Exposé dans l'API publique. L'argent reste sur l'organisateur principal.
 */
class CoOrganizerTest extends TestCase
{
    use RefreshDatabase;

    private function org(string $name): Organizer
    {
        return Organizer::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);
    }

    public function test_public_show_exposes_co_organizer(): void
    {
        $main = $this->org('OVRF');
        $co = $this->org('Bantu Fashion');

        $event = Event::create([
            'organizer_id' => $main->id, 'co_organizer_id' => $co->id,
            'title' => 'Chill Expo', 'slug' => 'chill-expo-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved', 'is_active' => true,
        ]);

        $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('event.organizer.name', 'OVRF')
            ->assertJsonPath('event.co_organizer.name', 'Bantu Fashion');
    }

    public function test_no_co_organizer_is_null(): void
    {
        $main = $this->org('Solo Org');
        $event = Event::create([
            'organizer_id' => $main->id,
            'title' => 'Solo', 'slug' => 'solo-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved', 'is_active' => true,
        ]);

        $this->getJson("/api/client/events/{$event->slug}")
            ->assertOk()
            ->assertJsonPath('event.co_organizer', null);
    }

    public function test_bantu_fashion_seeder_creates_org_and_links_chill_expo(): void
    {
        $ovrf = $this->org('OVRF');
        $event = Event::create([
            'organizer_id' => $ovrf->id,
            'title' => 'Chill Expo 1', 'slug' => 'chill-expo-1-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved', 'is_active' => true,
        ]);

        (new BantuFashionSeeder())->run();

        $bantu = Organizer::where('slug', 'bantu-fashion')->first();
        $this->assertNotNull($bantu);
        $this->assertSame('Bantu Fashion', $bantu->name);
        $this->assertSame($bantu->id, $event->fresh()->co_organizer_id);

        // Idempotent
        (new BantuFashionSeeder())->run();
        $this->assertSame(1, Organizer::where('slug', 'bantu-fashion')->count());
    }
}
