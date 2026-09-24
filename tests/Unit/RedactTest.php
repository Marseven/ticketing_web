<?php

namespace Tests\Unit;

use App\Support\Redact;
use PHPUnit\Framework\TestCase;

/**
 * Masquage des données personnelles avant journalisation.
 *
 * La réponse entière de la passerelle partait au journal : numéro, e-mail et
 * nom de chaque acheteur. Le planificateur interrogeant les factures toutes
 * les cinq minutes, cela faisait des centaines de copies par jour dans un
 * fichier sans rotation.
 *
 * Deux exigences opposées : ne plus conserver de quoi identifier quelqu'un,
 * mais garder de quoi diagnostiquer — sans quoi on masque tout et on se prive
 * des journaux le jour où un paiement se perd.
 */
class RedactTest extends TestCase
{
    public function test_a_phone_keeps_only_its_last_two_digits(): void
    {
        $out = Redact::payload(['payer_msisdn' => '24177855949']);

        $this->assertSame('•••••••••49', $out['payer_msisdn']);
        $this->assertStringNotContainsString('778559', $out['payer_msisdn']);
    }

    public function test_an_email_keeps_only_its_domain(): void
    {
        // Le domaine suffit à distinguer un client d'un robot ; l'identifiant
        // ne sert à rien au diagnostic.
        $out = Redact::payload(['payer_email' => 'aristide.mebodo@example.ga']);

        $this->assertSame('a•••@example.ga', $out['payer_email']);
    }

    public function test_a_name_keeps_only_its_initial(): void
    {
        $out = Redact::payload(['payer_name' => 'Richard Mebodo']);

        $this->assertSame('R•••', $out['payer_name']);
    }

    public function test_a_secret_disappears_entirely(): void
    {
        $out = Redact::payload(['password' => 'hunter2', 'shared_key' => 'abc', 'token' => 'xyz']);

        $this->assertSame(['password' => '[masqué]', 'shared_key' => '[masqué]', 'token' => '[masqué]'], $out);
    }

    public function test_what_serves_the_diagnosis_is_left_alone(): void
    {
        // Tout l'intérêt : le journal doit rester exploitable.
        $entree = [
            'bill_id' => '5576436358',
            'state' => 'unpaid',
            'amount' => 307693,
            'amount_paid' => 0,
            'payment_system_name' => 'airtelmoney',
            'external_reference' => 'PAYOUT_123_ABCD',
        ];

        $this->assertSame($entree, Redact::payload($entree));
    }

    public function test_a_key_that_merely_ends_in_name_is_not_a_person(): void
    {
        // « payment_system_name » n'est pas un nom de personne. Masquer par
        // sous-chaîne aurait rendu le journal illisible au diagnostic.
        $out = Redact::payload(['payment_system_name' => 'moovmoney']);

        $this->assertSame('moovmoney', $out['payment_system_name']);
    }

    public function test_nested_payloads_are_masked_too(): void
    {
        // Les rappels arrivent imbriqués : `webhook_data.payer_msisdn`.
        $out = Redact::payload([
            'webhook_data' => ['payer_msisdn' => '24177855949', 'state' => 'processed'],
        ]);

        $this->assertSame('•••••••••49', $out['webhook_data']['payer_msisdn']);
        $this->assertSame('processed', $out['webhook_data']['state']);
    }

    public function test_empty_and_missing_values_do_not_crash(): void
    {
        $out = Redact::payload([
            'payer_msisdn' => null, 'payer_email' => '', 'payer_name' => '   ',
            'payer_address' => null,
        ]);

        $this->assertNull($out['payer_msisdn']);
        $this->assertSame('', $out['payer_email']);
        $this->assertSame('[masqué]', $out['payer_name']);
    }

    public function test_a_malformed_email_is_not_leaked_by_accident(): void
    {
        $out = Redact::payload(['payer_email' => 'pas-une-adresse']);

        $this->assertSame('[masqué]', $out['payer_email']);
    }

    public function test_a_very_short_phone_is_masked_rather_than_shown(): void
    {
        $this->assertSame('[masqué]', Redact::payload(['phone' => '7'])['phone']);
    }
}
