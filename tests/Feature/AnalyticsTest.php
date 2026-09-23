<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mesure de fréquentation.
 *
 * Deux exigences se tiennent : rien ne doit être chargé tant qu'aucun
 * fournisseur n'est configuré, et une mesure qui dépose des cookies ne doit
 * pas démarrer sans le consentement du visiteur — ce qu'impose la loi
 * n° 001/2011 relative à la protection des données à caractère personnel.
 *
 * Les valeurs sont lues par `config()` et jamais par `env()` : une fois la
 * configuration mise en cache, `env()` renvoie null partout ailleurs, et la
 * mesure s'éteindrait silencieusement au premier déploiement optimisé.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function home(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    public function test_nothing_is_loaded_by_default(): void
    {
        config(['analytics.provider' => 'none']);

        $html = $this->home();

        $this->assertStringNotContainsString('googletagmanager', $html);
        $this->assertStringNotContainsString('plausible', $html);
    }

    public function test_a_provider_without_its_settings_stays_silent(): void
    {
        // Mieux vaut ne rien charger qu'un script incomplet qui échoue.
        config(['analytics.provider' => 'ga4', 'analytics.site_id' => null]);

        $this->assertStringNotContainsString('googletagmanager', $this->home());
    }

    public function test_a_cookieless_provider_loads_directly(): void
    {
        config(['analytics.provider' => 'plausible', 'analytics.domain' => 'primea.ga']);

        $html = $this->home();

        $this->assertStringContainsString('data-domain="primea.ga"', $html);
        // Sans cookie, pas de consentement à demander : aucune bannière.
        $this->assertStringNotContainsString('Accepter', $html);
    }

    public function test_a_self_hosted_script_url_is_honoured(): void
    {
        config([
            'analytics.provider' => 'umami',
            'analytics.site_id' => 'abc-123',
            'analytics.script_url' => 'https://stats.primea.ga/script.js',
        ]);

        $html = $this->home();

        $this->assertStringContainsString('https://stats.primea.ga/script.js', $html);
        $this->assertStringContainsString('data-website-id="abc-123"', $html);
    }

    public function test_google_analytics_waits_for_consent(): void
    {
        config(['analytics.provider' => 'ga4', 'analytics.site_id' => 'G-TEST123']);

        $html = $this->home();

        // L'identifiant est présent, mais le script de Google n'est PAS
        // chargé par la page : il ne l'est qu'après un clic sur « Accepter ».
        $this->assertStringContainsString('G-TEST123', $html);
        $this->assertStringContainsString('Accepter', $html);
        $this->assertStringContainsString('Refuser', $html);
        $this->assertStringNotContainsString('<script async src="https://www.googletagmanager.com', $html);
    }

    public function test_google_analytics_is_told_not_to_profile(): void
    {
        config(['analytics.provider' => 'ga4', 'analytics.site_id' => 'G-TEST123']);

        $html = $this->home();

        $this->assertStringContainsString('anonymize_ip', $html);
        $this->assertStringContainsString('allow_ad_personalization_signals', $html);
    }

    public function test_the_settings_are_declared_in_the_config_file(): void
    {
        // Garde-fou contre la panne déjà vécue sur les paiements : toute
        // valeur lue par le code doit exister dans config/, sinon elle est
        // vide dès que la configuration est mise en cache.
        $source = file_get_contents(resource_path('views/partials/analytics.blade.php'));

        // `env('...')` en PHP — à ne pas confondre avec le `env()` du CSS,
        // qui sert aux encoches d'écran et n'a rien à voir.
        $this->assertStringNotContainsString("env('", $source);
        $this->assertStringContainsString("config('analytics.provider'", $source);
    }
}
