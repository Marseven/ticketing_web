<?php

namespace Tests\Unit;

use App\Rules\PhoneNumberRule;
use PHPUnit\Framework\TestCase;

/**
 * Format des numéros de téléphone.
 *
 * Les champs téléphone n'avaient aucun contrôle : « abc » ou « 12 » partaient
 * jusqu'à la passerelle de paiement avant d'être rejetés, et le client voyait
 * un échec de paiement là où il avait simplement mal saisi son numéro.
 *
 * Le risque inverse est plus grave encore : une règle trop stricte refuse des
 * clients légitimes au moment de payer. D'où ces cas d'acceptation, qui
 * couvrent toutes les façons d'écrire le même numéro au Gabon.
 */
class PhoneNumberRuleTest extends TestCase
{
    private function echecs(mixed $valeur): array
    {
        $messages = [];
        (new PhoneNumberRule())->validate('phone', $valeur, function ($m) use (&$messages) {
            $messages[] = $m;
        });

        return $messages;
    }

    /**
     * @dataProvider formesValides
     */
    public function test_a_real_number_is_accepted(string $numero): void
    {
        $this->assertSame([], $this->echecs($numero), "refusé à tort : {$numero}");
    }

    public static function formesValides(): array
    {
        return [
            'abonné seul' => ['77855949'],
            'national avec zéro' => ['077855949'],
            'international' => ['+24177855949'],
            'international sans plus' => ['24177855949'],
            'avec espaces' => ['+241 77 85 59 49'],
            'avec tirets' => ['077-85-59-49'],
            'indicatif et zéro' => ['+241 0 77 85 59 49'],
            'entre parenthèses' => ['(+241) 77855949'],
            'autre pays' => ['+33 6 12 34 56 78'],
        ];
    }

    /**
     * @dataProvider formesInvalides
     */
    public function test_junk_is_refused(mixed $valeur): void
    {
        $this->assertNotSame([], $this->echecs($valeur), "accepté à tort : " . var_export($valeur, true));
    }

    public static function formesInvalides(): array
    {
        return [
            'vide' => [''],
            'lettres' => ['abcdefgh'],
            'trop court' => ['1234567'],
            'beaucoup trop court' => ['12'],
            'ponctuation seule' => ['++--  ()'],
            'trop long' => ['1234567890123456'],
            'gabonais tronqué' => ['+241 77 44 36'],
            'gabonais trop long' => ['+241 77 85 59 49 12'],
            'phrase' => ['appelez-moi'],
        ];
    }

    public function test_the_leading_zero_after_the_country_code_is_tolerated(): void
    {
        // Le zéro national survit souvent à l'ajout de l'indicatif. Le refuser
        // bloquait une saisie parfaitement courante — et au pire moment, celui
        // du paiement. C'est le cas qui a fait échouer la suite quand la règle
        // exigeait huit chiffres nus après « 241 ».
        $this->assertSame([], $this->echecs('241077855949'));
    }

    public function test_the_message_says_what_is_wrong(): void
    {
        // « Le numéro est invalide » n'aide personne à corriger sa saisie.
        $this->assertStringContainsString('court', $this->echecs('1234567')[0]);
        $this->assertStringContainsString('long', $this->echecs('1234567890123456')[0]);
        $this->assertStringContainsString('huit chiffres', $this->echecs('+241 77 44 36')[0]);
    }
}
