<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le statut affiché (Actif/Inactif) dérive de is_active : le front lit
 * category.status, exposé par un accessor. Corrige l'affichage « Inactif »
 * alors que les catégories sont actives.
 */
class CategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_accessor_reflects_is_active(): void
    {
        $active = Category::create(['name' => 'A', 'slug' => 'a-' . uniqid(), 'is_active' => true]);
        $inactive = Category::create(['name' => 'B', 'slug' => 'b-' . uniqid(), 'is_active' => false]);

        $this->assertSame('active', $active->status);
        $this->assertSame('inactive', $inactive->status);
        // Exposé dans la sérialisation JSON (ce que consomme le front)
        $this->assertSame('active', $active->toArray()['status']);
    }

    public function test_seeded_categories_are_active(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->assertGreaterThan(0, Category::count());
        $this->assertTrue(Category::get()->every(fn ($c) => $c->status === 'active'));
    }
}
