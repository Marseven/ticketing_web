<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle de la source du rappel d'encaissement e-billing.
 *
 * Ce point d'entrée fait émettre des billets : il déclare un paiement réglé.
 * Deux défauts le rendaient franchissable.
 *
 * 1. Il s'OUVRAIT quand rien n'était configuré — l'absence de réglage valait
 *    autorisation. Sur un serveur où la variable manque, n'importe qui pouvait
 *    se faire émettre des billets gratuitement.
 *
 * 2. Même avec un secret, l'adresse IP restait une porte alternative. Or
 *    l'application fait confiance à tous les proxies : l'en-tête
 *    « X-Forwarded-For » écrit par l'appelant devenait son adresse.
 */
class EbillingWebhookAuthTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/webhooks/ebilling';

    private function appel(array $entetes = [], string $requete = '')
    {
        return $this->postJson(self::URL . $requete, [
            'reference' => 'ORD-INEXISTANTE',
            'billingid' => '123',
            'status' => 'processed',
        ], $entetes);
    }

    public function test_nothing_configured_means_no_access(): void
    {
        // L'absence de réglage n'est pas une autorisation. Refuser ne coûte
        // rien : `payments:check-pending` interroge la passerelle toutes les
        // cinq minutes et rattrape ce que le rappel n'a pas confirmé.
        config([
            'services.ebilling.webhook_secret' => null,
            'services.ebilling.webhook_allowed_ips' => '',
        ]);

        $this->appel()->assertStatus(403);
    }

    public function test_a_valid_secret_opens_the_door(): void
    {
        config(['services.ebilling.webhook_secret' => 'le-bon-secret']);

        // La référence n'existe pas : ce qui compte est que l'autorisation
        // soit franchie, donc que la réponse ne soit pas un 403.
        $this->appel(['X-Webhook-Secret' => 'le-bon-secret'])->assertStatus(404);
    }

    public function test_the_secret_may_travel_in_the_url(): void
    {
        // e-billing ne pose que l'URL de notification, pas les en-têtes.
        config(['services.ebilling.webhook_secret' => 'le-bon-secret']);

        $this->appel([], '?token=le-bon-secret')->assertStatus(404);
    }

    public function test_a_wrong_secret_is_refused(): void
    {
        config(['services.ebilling.webhook_secret' => 'le-bon-secret']);

        $this->appel(['X-Webhook-Secret' => 'presque'])->assertStatus(403);
        $this->appel()->assertStatus(403);
    }

    public function test_a_known_address_is_accepted_without_the_token(): void
    {
        // CHOIX ASSUMÉ : le jeton et l'adresse sont deux voies alternatives.
        // Une notification de paiement ne doit jamais être bloquée parce que la
        // facture avait été créée avant que le jeton n'existe, ou parce qu'un
        // intermédiaire a raboté l'URL — le client aurait payé sans recevoir
        // son billet.
        //
        // ⚠️ Cette voie se falsifie : l'application fait confiance à tous les
        // proxies, donc « X-Forwarded-For » fourni par l'appelant devient son
        // adresse. C'est le jeton qui protège réellement ; la liste d'adresses
        // est là pour ne rien perdre, pas pour arrêter un attaquant. Le test le
        // constate plutôt que de le taire.
        config([
            'services.ebilling.webhook_secret' => 'le-bon-secret',
            'services.ebilling.webhook_allowed_ips' => '41.158.0.1',
        ]);

        $this->appel(['X-Forwarded-For' => '41.158.0.1'])->assertStatus(404);
    }

    public function test_an_unknown_address_without_token_is_still_refused(): void
    {
        config([
            'services.ebilling.webhook_secret' => 'le-bon-secret',
            'services.ebilling.webhook_allowed_ips' => '41.158.0.1',
        ]);

        $this->appel(['X-Forwarded-For' => '198.51.100.7'])->assertStatus(403);
    }

    public function test_the_allow_list_still_works_when_it_is_the_only_setting(): void
    {
        // Sans secret, la liste d'adresses reste le seul contrôle possible.
        // Elle est fragile — d'où l'avertissement au journal — mais elle ne
        // doit pas cesser de fonctionner pour autant.
        config([
            'services.ebilling.webhook_secret' => null,
            'services.ebilling.webhook_allowed_ips' => '127.0.0.1',
        ]);

        $this->appel()->assertStatus(404);
    }
}
