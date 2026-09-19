<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le sélecteur de catégorie des formulaires d'événement (?all=1) doit lister
 * TOUTES les catégories actives, pas seulement celles ayant déjà un événement
 * publié (sinon on ne peut choisir qu'une catégorie).
 */
class CategoryFormListTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_mode_returns_every_active_category(): void
    {
        Category::create(['name' => 'Concert', 'slug' => 'concert', 'is_active' => true]);
        Category::create(['name' => 'Théâtre', 'slug' => 'theatre', 'is_active' => true]);
        Category::create(['name' => 'Ancienne', 'slug' => 'ancienne', 'is_active' => false]);

        $res = $this->getJson('/api/v1/categories?all=1');

        $res->assertOk()->assertJsonPath('success', true);
        $names = collect($res->json('categories'))->pluck('name');
        $this->assertContains('Concert', $names);
        $this->assertContains('Théâtre', $names);      // active mais sans event publié
        $this->assertNotContains('Ancienne', $names);  // inactive exclue
    }

    public function test_default_mode_only_returns_categories_with_published_events(): void
    {
        $withEvent = Category::create(['name' => 'Concert', 'slug' => 'concert', 'is_active' => true]);
        Category::create(['name' => 'Théâtre', 'slug' => 'theatre', 'is_active' => true]);

        $organizer = Organizer::create(['name' => 'Org', 'slug' => 'o-' . uniqid(), 'status' => 'active', 'is_active' => true]);
        Event::create([
            'organizer_id' => $organizer->id, 'category_id' => $withEvent->id,
            'title' => 'E', 'slug' => 'e-' . uniqid(), 'description' => 'x',
            'status' => 'published', 'is_active' => true, 'approval_status' => 'approved',
        ]);

        $names = collect($this->getJson('/api/v1/categories')->json('categories'))->pluck('name');
        $this->assertContains('Concert', $names);
        $this->assertNotContains('Théâtre', $names); // pas d'event publié -> exclue en mode par défaut
    }
}
