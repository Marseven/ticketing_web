<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aperçu d'un lien partagé.
 *
 * Au Gabon un billet se partage par WhatsApp : l'aperçu du lien est la
 * première chose que voit l'acheteur. Or l'application est une SPA, et
 * WhatsApp comme Facebook ou les moteurs de recherche lisent le HTML servi
 * SANS exécuter le JavaScript. Les métadonnées doivent donc être résolues
 * côté serveur, sinon tous les liens d'événements se ressemblent.
 */
class PageMetaTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(array $overrides = []): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $venue = Venue::create([
            'organizer_id' => $organizer->id, 'name' => 'Palais des Sports',
            'address' => 'Boulevard Triomphal', 'city' => 'Libreville',
        ]);

        $event = Event::create(array_merge([
            'organizer_id' => $organizer->id, 'venue_id' => $venue->id,
            'title' => 'Nuit des Champions', 'slug' => 'nuit-des-champions',
            'description' => "Gala de boxe avec les meilleurs combattants d'Afrique centrale.",
            'status' => 'published', 'approval_status' => 'approved',
            'image_url' => 'https://primea.ga/affiche.jpg',
        ], $overrides));

        EventSchedule::create([
            'event_id' => $event->id,
            'starts_at' => '2026-12-19 21:00:00', 'ends_at' => '2026-12-20 02:00:00',
            'status' => 'active',
        ]);

        return $event->fresh();
    }

    public function test_a_shared_event_link_carries_the_event_not_the_site(): void
    {
        $this->publishedEvent();

        $html = $this->get('/nuit-des-champions')->assertOk()->getContent();

        $this->assertStringContainsString('Nuit des Champions', $html);
        $this->assertStringContainsString('property="og:image" content="https://primea.ga/affiche.jpg"', $html);
        // Une affiche mérite la grande vignette.
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
    }

    public function test_the_preview_says_when_and_where(): void
    {
        $this->publishedEvent();

        $html = $this->get('/nuit-des-champions')->assertOk()->getContent();

        $this->assertStringContainsString('décembre 2026', $html);
        $this->assertStringContainsString('Palais des Sports', $html);
        $this->assertStringContainsString('Libreville', $html);
    }

    public function test_the_details_page_shares_the_same_preview(): void
    {
        $this->publishedEvent();

        // `/{slug}` mène à l'achat et `/{slug}/details` à la fiche : les deux
        // formes circulent dans les conversations.
        $html = $this->get('/nuit-des-champions/details')->assertOk()->getContent();

        $this->assertStringContainsString('Nuit des Champions', $html);
    }

    public function test_search_engines_get_the_event_as_structured_data(): void
    {
        $this->publishedEvent();

        $html = $this->get('/nuit-des-champions')->assertOk()->getContent();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Event"', $html);
        $this->assertStringContainsString('"startDate":"2026-12-19T21:00:00', $html);
    }

    public function test_an_end_before_the_start_is_left_out(): void
    {
        // Des séances mal saisies existent en base. Une fin antérieure au
        // début ferait rejeter tout le bloc de données structurées par les
        // moteurs : mieux vaut publier l'événement sans sa date de fin.
        $event = $this->publishedEvent();
        $event->schedules()->update([
            'starts_at' => '2026-12-19 21:00:00',
            'ends_at' => '2026-12-10 23:30:00',
        ]);

        $html = $this->get('/nuit-des-champions')->assertOk()->getContent();

        $this->assertStringContainsString('"startDate":"2026-12-19T21:00:00', $html);
        $this->assertStringNotContainsString('"endDate"', $html);
    }

    public function test_an_unpublished_event_falls_back_to_the_site_preview(): void
    {
        $this->publishedEvent(['status' => 'draft']);

        $html = $this->get('/nuit-des-champions')->assertOk()->getContent();

        $this->assertStringNotContainsString('Nuit des Champions', $html);
        $this->assertStringContainsString('Primea', $html);
    }

    public function test_an_unknown_url_still_serves_the_application(): void
    {
        $html = $this->get('/cette-page-nexiste-pas')->assertOk()->getContent();

        $this->assertStringContainsString('Primea', $html);
    }

    public function test_a_static_page_has_its_own_title(): void
    {
        $html = $this->get('/terms')->assertOk()->getContent();

        // L'apostrophe est échappée par Blade dans le HTML servi.
        $this->assertStringContainsString('Conditions générales d', $html);
        $this->assertStringContainsString('<title>', $html);
    }

    public function test_the_legal_notice_is_kept_out_of_search_engines(): void
    {
        // La page attend l'identité de l'éditeur : elle reste consultable pour
        // être relue et complétée, mais elle n'a rien à faire dans un index
        // tant qu'elle porte des champs à remplir.
        $html = $this->get('/legal-notice')->assertOk()->getContent();

        $this->assertStringContainsString('name="robots" content="noindex, nofollow"', $html);
    }

    public function test_an_ordinary_page_is_left_indexable(): void
    {
        $this->assertStringNotContainsString('name="robots"', $this->get('/terms')->getContent());
    }

    public function test_the_admin_area_never_queries_events(): void
    {
        // La résolution tourne sur chaque page servie : elle ne doit pas
        // toucher la base pour des chemins qui ne désignent jamais un
        // événement, sinon chaque écran d'administration paie une requête.
        \Illuminate\Support\Facades\DB::enableQueryLog();

        $this->get('/admin/events')->assertOk();

        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog())
            ->pluck('query')
            ->filter(fn ($q) => str_contains($q, 'from "events"') || str_contains($q, 'from `events`'));

        $this->assertCount(0, $queries, 'aucune requête sur les événements pour une page admin');
    }
}
