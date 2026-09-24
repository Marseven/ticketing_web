<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle de l'origine du rappel d'encaissement e-billing.
 *
 * Ce point d'entrée fait émettre des billets : il déclare un paiement réglé.
 *
 * **L'adresse est le seul contrôle possible.** E-billing rappelle une URL
 * configurée dans le compte marchand — « https://primea.ga/api/v1/webhooks/
 * ebilling », sans paramètre. Le jeton qu'on plaçait dans l'URL de la facture
 * ne lui revenait donc jamais, et l'exiger aurait bloqué toutes les
 * confirmations de paiement : clients débités, billets non émis.
 *
 * Restait à ne pas se laisser dicter cette adresse. L'application fait
 * confiance à tous les proxies : l'ancienne version lisait « X-Forwarded-For »
 * tel que l'appelant l'avait écrit, et il suffisait d'y placer une adresse
 * autorisée pour entrer.
 */
class EbillingWebhookAuthTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/webhooks/ebilling';
    private const EBILLING = '41.158.0.1';

    private function appel(array $entetes = [])
    {
        return $this->postJson(self::URL, [
            'reference' => 'ORD-INEXISTANTE',
            'billingid' => '123',
            'state' => 'processed',
        ], $entetes);
    }

    public function test_without_a_declared_address_nothing_is_accepted(): void
    {
        // L'absence de réglage n'est pas une autorisation. Refuser coûte au
        // pire cinq minutes : `payments:check-pending` rattrape la
        // confirmation auprès de la passerelle.
        config(['services.ebilling.webhook_allowed_ips' => '']);

        $this->appel()->assertStatus(403);
    }

    public function test_the_declared_address_opens_the_door(): void
    {
        // Les requêtes de test arrivent de 127.0.0.1. La référence n'existe
        // pas : ce qui compte est que le contrôle d'origine soit franchi.
        config(['services.ebilling.webhook_allowed_ips' => '127.0.0.1']);

        $this->appel()->assertStatus(404);
    }

    public function test_an_address_outside_the_list_is_refused(): void
    {
        config(['services.ebilling.webhook_allowed_ips' => self::EBILLING]);

        $this->appel()->assertStatus(403);
    }

    public function test_behind_the_host_proxy_the_forwarded_origin_is_read(): void
    {
        // Cas réel en production : la requête arrive par le proxy de
        // l'hébergeur, et l'adresse d'e-billing n'est lisible que dans
        // « X-Forwarded-For ». REMOTE_ADDR vaut ici 127.0.0.1, donc un
        // intermédiaire local : l'en-tête est alors pris en compte.
        config(['services.ebilling.webhook_allowed_ips' => self::EBILLING]);

        $this->appel(['X-Forwarded-For' => self::EBILLING])->assertStatus(404);
    }

    public function test_a_caller_from_the_internet_cannot_forge_its_origin(): void
    {
        // Le contournement d'origine : écrire soi-même une adresse autorisée.
        // Une requête venue d'Internet a un REMOTE_ADDR public — ici celui de
        // l'appelant — et aucun en-tête ne doit pouvoir la rattraper.
        config(['services.ebilling.webhook_allowed_ips' => self::EBILLING]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->postJson(self::URL, ['reference' => 'ORD-X', 'state' => 'processed'], [
                'X-Forwarded-For' => self::EBILLING,
            ])
            ->assertStatus(403);
    }

    public function test_only_the_last_forwarded_entry_counts(): void
    {
        // Un appelant peut insérer des entrées, mais elles se placent AVANT
        // celle que le proxy ajoute en notant de qui il a reçu la requête.
        // C'est cette dernière, et elle seule, qui est lue.
        config(['services.ebilling.webhook_allowed_ips' => self::EBILLING]);

        $this->appel(['X-Forwarded-For' => self::EBILLING . ', 198.51.100.9'])
            ->assertStatus(403);
    }

    public function test_several_addresses_may_be_declared(): void
    {
        // Les passerelles changent d'adresse de sortie : la liste en accepte
        // plusieurs, séparées par des virgules.
        config(['services.ebilling.webhook_allowed_ips' => '41.158.0.1, 41.158.0.2, 127.0.0.1']);

        $this->appel()->assertStatus(404);
    }
}
