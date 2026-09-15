<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * catalog:reset vide événements / catégories / lieux / paiements (+ dépendances)
 * en conservant utilisateurs et organisateurs, et reseed les catégories de base.
 */
class CatalogResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_clears_catalog_keeps_users_and_reseeds_categories(): void
    {
        $user = User::create([
            'name' => 'Kept', 'email' => 'keep@x.test', 'password' => bcrypt('x'),
        ]);
        $org = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(), 'status' => 'active', 'is_active' => true,
        ]);
        $cat = Category::create(['name' => 'Ancienne', 'slug' => 'ancienne-' . uniqid()]);
        $venue = Venue::create([
            'name' => 'Lieu', 'organizer_id' => $org->id, 'city' => 'Libreville',
            'address' => 'x', 'country' => 'Gabon', 'status' => 'active',
        ]);
        Event::create([
            'organizer_id' => $org->id, 'category_id' => $cat->id, 'venue_id' => $venue->id,
            'title' => 'Ev', 'slug' => 'ev-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'approval_status' => 'approved',
        ]);

        $this->assertSame(1, Event::count());
        $this->assertSame(1, Venue::count());

        $this->artisan('catalog:reset', ['--force' => true, '--seed' => true])
            ->assertExitCode(0);

        // Catalogue/ventes vidés
        $this->assertSame(0, Event::count());
        $this->assertSame(0, Venue::count());
        $this->assertSame(0, DB::table('tickets')->count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('payments')->count());

        // Utilisateurs & organisateurs conservés
        $this->assertNotNull($user->fresh());
        $this->assertSame(1, Organizer::count());

        // Catégories de base reseedées (l'ancienne a disparu, les défauts sont là)
        $this->assertGreaterThan(0, Category::count());
        $this->assertNull(Category::where('name', 'Ancienne')->first());
        $this->assertNotNull(Category::where('name', 'Autres')->first());
    }

    public function test_reset_without_seed_leaves_categories_empty(): void
    {
        Category::create(['name' => 'X', 'slug' => 'x-' . uniqid()]);

        $this->artisan('catalog:reset', ['--force' => true])->assertExitCode(0);

        $this->assertSame(0, Category::count());
    }
}
