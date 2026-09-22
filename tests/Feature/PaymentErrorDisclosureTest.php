<?php

namespace Tests\Feature;

use App\Exceptions\PaymentGatewayUnavailable;
use App\Services\EBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ce qu'un acheteur voit quand la passerelle ne répond pas.
 *
 * Une page d'achat a affiché « EBILLING_USERNAME n'est pas configuré dans
 * .env » à un client : inutile pour lui, et ça expose le fonctionnement
 * interne. Le détail appartient au journal ; l'écran reçoit un message
 * standard.
 *
 * La cause de cet incident mérite d'être retenue : le service lisait ses
 * identifiants avec `env()`, or `env()` renvoie null hors des fichiers de
 * configuration dès que celle-ci est mise en cache (`artisan optimize`). Le
 * paiement tombait donc en panne au premier déploiement optimisé.
 */
class PaymentErrorDisclosureTest extends TestCase
{
    use RefreshDatabase;

    private function forgetCredentials(): void
    {
        config([
            'services.ebilling.username' => null,
            'services.ebilling.shared_key' => null,
            'services.ebilling.server_url' => null,
            'services.ebilling.post_url' => null,
        ]);
    }

    public function test_credentials_are_read_from_config_not_the_environment(): void
    {
        // Ce que ferait la config en cache : l'environnement porte la valeur,
        // mais la configuration, non. Le service doit suivre la configuration.
        putenv('EBILLING_USERNAME=depuis-env');
        $this->forgetCredentials();

        try {
            new EBillingService();
            $this->fail('le service aurait dû signaler une configuration incomplète');
        } catch (PaymentGatewayUnavailable $e) {
            $this->assertStringContainsString('EBILLING_USERNAME', $e->getMessage());
        } finally {
            putenv('EBILLING_USERNAME');
        }
    }

    public function test_the_technical_detail_stays_out_of_the_customer_message(): void
    {
        $this->forgetCredentials();

        try {
            new EBillingService();
            $this->fail('exception attendue');
        } catch (PaymentGatewayUnavailable $e) {
            $public = $e->publicMessage();

            // Le message public ne doit rien révéler de l'interne.
            $this->assertStringNotContainsString('EBILLING', $public);
            $this->assertStringNotContainsString('.env', $public);
            $this->assertStringNotContainsString('config', $public);
            $this->assertStringContainsString('momentanément indisponible', $public);
        }
    }

    public function test_the_message_shown_to_the_buyer_is_the_standard_one(): void
    {
        $this->assertSame(
            'Le paiement est momentanément indisponible. Merci de réessayer dans quelques minutes.',
            PaymentGatewayUnavailable::PUBLIC_MESSAGE
        );
    }

    public function test_the_payment_endpoint_never_returns_a_raw_exception_message(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/PaymentController.php'));

        $this->assertStringNotContainsString(
            "'message' => 'Erreur technique: ' . \$e->getMessage()",
            $source,
            'le message brut d\'une exception ne doit pas repartir vers l\'acheteur'
        );
    }
}
