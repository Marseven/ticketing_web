<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

/**
 * Conformité TOTP, vérifiée contre les vecteurs de test officiels de la
 * RFC 6238 (annexe B).
 *
 * C'est la seule preuve qui vaille : un code que nous serions seuls à
 * calculer correctement ne servirait à rien, puisque c'est Google
 * Authenticator qui l'affiche à l'utilisateur. Si ces vecteurs passent,
 * n'importe quelle application conforme s'accordera avec nous.
 */
class TotpServiceTest extends TestCase
{
    /** Le secret des vecteurs RFC : les 20 octets ASCII « 12345678901234567890 ». */
    private function rfcSecret(): string
    {
        return (new TotpService())->base32Encode('12345678901234567890');
    }

    public function test_the_rfc_6238_vectors_match(): void
    {
        $totp = new TotpService(digits: 8);
        $secret = $this->rfcSecret();

        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
            20000000000 => '65353130',
        ];

        foreach ($vectors as $time => $expected) {
            $this->assertSame($expected, $totp->codeAt($secret, $time), "vecteur RFC à T={$time}");
        }
    }

    public function test_base32_round_trips(): void
    {
        $totp = new TotpService();

        $this->assertSame('12345678901234567890', $totp->base32Decode($this->rfcSecret()));
    }

    public function test_a_generated_secret_is_usable_base32(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertSame(20, strlen($totp->base32Decode($secret)));
        $this->assertNotSame($secret, $totp->generateSecret(), 'deux secrets ne se répètent pas');
    }

    public function test_the_current_code_is_accepted(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();
        $now = 1_700_000_000;

        $this->assertTrue($totp->verify($secret, $totp->codeAt($secret, $now), $now));
    }

    public function test_a_neighbouring_window_is_tolerated(): void
    {
        // L'horloge du téléphone dérive, et recopier six chiffres prend du
        // temps : refuser la fenêtre voisine rendrait le 2FA pénible.
        $totp = new TotpService();
        $secret = $totp->generateSecret();
        $now = 1_700_000_000;

        $this->assertTrue($totp->verify($secret, $totp->codeAt($secret, $now - 30), $now));
        $this->assertTrue($totp->verify($secret, $totp->codeAt($secret, $now + 30), $now));
    }

    public function test_a_stale_code_is_refused(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();
        $now = 1_700_000_000;

        $this->assertFalse($totp->verify($secret, $totp->codeAt($secret, $now - 120), $now),
            'un code de deux minutes ne doit plus ouvrir');
    }

    public function test_another_secret_never_opens(): void
    {
        $totp = new TotpService();
        $now = 1_700_000_000;
        $mine = $totp->generateSecret();
        $theirs = $totp->generateSecret();

        $this->assertFalse($totp->verify($mine, $totp->codeAt($theirs, $now), $now));
    }

    public function test_malformed_input_is_refused_without_crashing(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();

        foreach (['', '12345', '1234567', 'abcdef', '   ', '000000'] as $junk) {
            $this->assertFalse($totp->verify($secret, $junk, 1_700_000_000), "refusé : « {$junk} »");
        }
    }

    public function test_the_provisioning_uri_carries_what_the_app_needs(): void
    {
        $totp = new TotpService();
        $uri = $totp->provisioningUri('JBSWY3DPEHPK3PXP', 'admin@primea.ga', 'Primea');

        $this->assertStringStartsWith('otpauth://totp/Primea:admin%40primea.ga?', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=Primea', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }
}
