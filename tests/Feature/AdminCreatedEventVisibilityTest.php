<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Un événement créé par un administrateur doit être visible du public.
 *
 * La file d'approbation existe pour que l'admin valide les événements des
 * ORGANISATEURS. Quand c'est l'admin lui-même qui crée l'événement, la
 * validation a déjà eu lieu : le laisser en attente le rend invisible sur le
 * site sans que rien ne le signale, et l'admin doit aller s'approuver
 * lui-même. C'est arrivé en production, sur un événement en vente.
 */
class AdminCreatedEventVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $type = UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $admin->user_type_id = $type->id;
        $admin->save();

        $role = Role::firstOrCreate(
            ['name' => Role::ADMIN],
            ['slug' => Role::ADMIN, 'label' => 'Administrateur', 'description' => 'Accès complet']
        );
        $admin->roles()->syncWithoutDetaching([$role->id]);

        return $admin->fresh('roles');
    }

    private function createEvent(array $overrides = [])
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $category = EventCategory::firstOrCreate(
            ['slug' => 'concert'],
            ['name' => 'Concert', 'is_active' => true]
        );

        Sanctum::actingAs($this->admin());

        return $this->postJson('/api/v1/admin/events', array_merge([
            'title' => 'Nuit des Champions',
            'description' => 'Gala de boxe',
            'organizer_id' => $organizer->id,
            'category_id' => $category->id,
            'status' => 'published',
            'schedules' => [[
                'starts_at' => now()->addMonths(3)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addMonths(3)->addHours(6)->format('Y-m-d H:i:s'),
            ]],
            'ticket_types' => [[
                'name' => 'Ghetto Bling', 'price' => 5000, 'capacity' => 8000,
            ]],
        ], $overrides));
    }

    public function test_an_event_created_by_an_admin_is_approved_on_the_spot(): void
    {
        $this->createEvent()->assertSuccessful();

        $event = Event::where('title', 'Nuit des Champions')->first();

        $this->assertNotNull($event);
        $this->assertSame('approved', $event->approval_status,
            'l\'admin est celui qui approuve : son propre événement ne doit pas attendre');
        $this->assertNotNull($event->approved_at);
        $this->assertNotNull($event->approved_by);
    }

    public function test_it_then_shows_up_on_the_public_listing(): void
    {
        $this->createEvent()->assertSuccessful();

        $listed = collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('title', 'Nuit des Champions');

        $this->assertNotNull($listed, 'un événement publié par l\'admin doit être visible du public');
    }

    public function test_a_draft_stays_out_of_the_public_listing(): void
    {
        // L'approbation automatique ne court-circuite pas le statut : un
        // brouillon reste un brouillon.
        $this->createEvent(['status' => 'draft'])->assertSuccessful();

        $listed = collect($this->getJson('/api/client/events')->assertOk()->json('events'))
            ->firstWhere('title', 'Nuit des Champions');

        $this->assertNull($listed);
    }

    public function test_the_active_flag_is_actually_written(): void
    {
        // `is_active` était absent de $fillable : la valeur envoyée par
        // l'écran partait en silence, et l'admin ne pouvait pas désactiver un
        // événement.
        $this->createEvent(['is_active' => false])->assertSuccessful();

        $event = Event::where('title', 'Nuit des Champions')->first();

        $this->assertFalse((bool) $event->is_active);
    }
}
