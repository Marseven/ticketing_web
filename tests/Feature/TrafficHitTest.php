<?php

namespace Tests\Feature;

use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Signalement d'une page consultée.
 *
 * Point d'entrée public — il doit l'être, la fréquentation vient surtout de
 * visiteurs non connectés — donc il n'écrit que ce qu'il fabrique lui-même.
 * Aucune adresse IP n'est conservée : l'empreinte mélange adresse, navigateur,
 * clé de l'application et date du jour, elle change à minuit et ne permet pas
 * de remonter à une personne.
 */
class TrafficHitTest extends TestCase
{
    use RefreshDatabase;

    private function hit(array $payload, array $headers = [])
    {
        return $this->postJson('/api/v1/traffic/hit', $payload, array_merge([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15',
        ], $headers));
    }

    public function test_a_visit_is_recorded(): void
    {
        $this->hit(['path' => '/chill-expo-1'])->assertOk()->assertJsonPath('counted', true);

        $view = PageView::first();

        $this->assertSame('/chill-expo-1', $view->path);
        $this->assertSame('mobile', $view->device);
        $this->assertSame(64, strlen($view->visitor_hash));
    }

    public function test_no_ip_address_is_ever_stored(): void
    {
        $this->hit(['path' => '/'], ['REMOTE_ADDR' => '41.158.12.34']);

        $stored = json_encode(PageView::first()->toArray());

        $this->assertStringNotContainsString('41.158.12.34', $stored);
    }

    public function test_the_same_visitor_is_recognised_within_the_day(): void
    {
        $this->hit(['path' => '/']);
        $this->hit(['path' => '/events']);

        // Deux pages vues, un seul visiteur : c'est ce qui rend le comptage
        // possible sans cookie.
        $this->assertSame(2, PageView::count());
        $this->assertSame(1, PageView::distinct('visitor_hash')->count('visitor_hash'));
    }

    public function test_the_query_string_is_dropped(): void
    {
        // Elle peut porter un jeton de récupération de billet ou une référence
        // de commande : rien de tout cela n'a sa place dans un journal.
        $this->hit(['path' => '/retrieve-ticket?token=SECRET123&ref=ORD-9']);

        $this->assertSame('/retrieve-ticket', PageView::first()->path);
    }

    public function test_a_robot_is_not_counted(): void
    {
        $this->hit(['path' => '/'], ['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->assertOk()
            ->assertJsonPath('counted', false);

        $this->assertSame(0, PageView::count());
    }

    public function test_a_link_preview_fetch_is_not_counted(): void
    {
        // WhatsApp et Facebook chargent la page pour fabriquer l'aperçu du
        // lien : ce n'est pas une visite.
        $this->hit(['path' => '/'], ['User-Agent' => 'facebookexternalhit/1.1'])
            ->assertJsonPath('counted', false);

        $this->assertSame(0, PageView::count());
    }

    public function test_the_referrer_is_reduced_to_its_source(): void
    {
        $this->hit(['path' => '/', 'referrer' => 'https://www.whatsapp.com/xyz?id=42']);

        $this->assertSame('whatsapp', PageView::first()->referrer_source);
    }

    public function test_an_internal_referrer_counts_as_direct(): void
    {
        // Le renvoi vient du site lui-même : ce n'est pas une provenance.
        $ownHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $this->hit(['path' => '/events', 'referrer' => "https://{$ownHost}/"]);

        $this->assertSame('direct', PageView::first()->referrer_source);
    }

    public function test_a_hit_without_a_path_is_refused(): void
    {
        $this->hit([])->assertStatus(422);
    }
}
