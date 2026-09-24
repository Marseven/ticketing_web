<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Diagnostic de la configuration des paiements.
 *
 * Un client a vu « EBILLING_USERNAME n'est pas configuré dans .env » sur sa
 * page d'achat, et personne ne pouvait dire depuis le serveur si les
 * identifiants arrivaient vraiment jusqu'à l'application. Cette commande
 * répond à la question — sans jamais imprimer un secret.
 */
class PaymentsDoctorTest extends TestCase
{
    use RefreshDatabase;

    private function credentials(array $overrides = []): void
    {
        config(array_merge([
            'services.ebilling.username' => 'primea-marchand',
            'services.ebilling.shared_key' => 'cle-partagee-secrete',
            'services.ebilling.server_url' => 'https://exemple.test/bills',
            'services.ebilling.post_url' => 'https://exemple.test/post',
            'services.ebilling.auth_mode' => 'basic',
            // Le jeton du rappel fait partie d'une configuration complète : il
            // protège la confirmation d'encaissement, qui fait émettre les
            // billets.
            'services.ebilling.webhook_secret' => 'jeton-de-rappel',
            'services.shap.api_id' => 'shap-id',
            'services.shap.api_secret' => 'shap-secret',
            'services.shap.base_url' => 'https://exemple.test/shap/',
        ], $overrides));
    }

    public function test_a_complete_configuration_passes(): void
    {
        $this->credentials();

        $this->artisan('payments:doctor')->assertSuccessful();
    }

    public function test_a_missing_credential_fails_and_is_named(): void
    {
        $this->credentials(['services.ebilling.shared_key' => null]);

        $this->artisan('payments:doctor')
            ->expectsOutputToContain('EBILLING_SHARED_KEY')
            ->assertFailed();
    }

    public function test_a_blank_string_counts_as_missing(): void
    {
        // Une variable présente mais vide dans le .env trompe l'œil.
        $this->credentials(['services.ebilling.username' => '   ']);

        $this->artisan('payments:doctor')->assertFailed();
    }

    public function test_missing_oauth_credentials_warn_without_crying_wolf(): void
    {
        // Sans identifiants Cognito, le service retombe sur Basic et le
        // paiement fonctionne encore : on signale, on n'alarme pas. Un
        // diagnostic rouge sur un système qui marche ne sert plus à rien.
        $this->credentials([
            'services.ebilling.auth_mode' => 'oauth',
            'services.ebilling.oauth_token_url' => '',
            'services.ebilling.oauth_client_id' => '',
            'services.ebilling.oauth_client_secret' => '',
        ]);

        $this->artisan('payments:doctor')
            ->expectsOutputToContain('EBILLING_OAUTH_CLIENT_ID')
            ->expectsOutputToContain('Basic')
            ->assertSuccessful();
    }

    public function test_no_secret_is_ever_printed(): void
    {
        $this->credentials();

        $this->artisan('payments:doctor');

        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringNotContainsString('cle-partagee-secrete', $output);
        $this->assertStringNotContainsString('shap-secret', $output);
        $this->assertStringNotContainsString('primea-marchand', $output);
    }

    public function test_the_customer_message_is_shown_when_the_service_cannot_start(): void
    {
        $this->credentials([
            'services.ebilling.username' => null,
            'services.ebilling.shared_key' => null,
            'services.ebilling.server_url' => null,
            'services.ebilling.post_url' => null,
        ]);

        $this->artisan('payments:doctor')
            ->expectsOutputToContain('momentanément indisponible')
            ->assertFailed();
    }
}
