<?php

namespace Tests\Feature;

use App\Services\EBillingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * E-billing (dépôt) : authentification commutable OAuth (Cognito) / Basic, avec
 * fallback automatique vers Basic quand l'OAuth échoue.
 */
class EbillingAuthTest extends TestCase
{
    private function setEnv(array $vars): void
    {
        foreach ($vars as $k => $v) {
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->setEnv([
            'EBILLING_USERNAME' => 'user',
            'EBILLING_SHARED_KEY' => 'sharedkey',
            'EBILLING_SERVER_URL' => 'https://ebill.test/e_bills',
            'EBILLING_POST_URL' => 'https://app.test/callback',
            'EBILLING_OAUTH_TOKEN_URL' => 'https://cognito.test/oauth2/token',
            'EBILLING_OAUTH_CLIENT_ID' => 'client-id',
            'EBILLING_OAUTH_CLIENT_SECRET' => 'client-secret',
            'EBILLING_OAUTH_SCOPE' => '',
        ]);
    }

    public function test_oauth_mode_sends_bearer_token(): void
    {
        $this->setEnv(['EBILLING_AUTH_MODE' => 'oauth']);
        Http::fake([
            'cognito.test/*' => Http::response(['access_token' => 'BEARER123', 'expires_in' => 3600, 'token_type' => 'Bearer'], 200),
            'ebill.test/*' => Http::response(['e_bill' => ['bill_id' => 'B1']], 201),
        ]);

        $res = (new EBillingService())->createBill(['amount' => 1000]);

        $this->assertTrue($res['success']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'ebill.test')
            && $r->hasHeader('Authorization', 'Bearer BEARER123'));
    }

    public function test_falls_back_to_basic_when_oauth_unauthorized(): void
    {
        $this->setEnv(['EBILLING_AUTH_MODE' => 'oauth']);
        Http::fake([
            'cognito.test/*' => Http::response(['access_token' => 'BEARER123', 'expires_in' => 3600], 200),
            // 1er appel (Bearer) => 401 ; 2e (Basic) => 201
            'ebill.test/*' => Http::sequence()
                ->push(['error' => 'unauthorized'], 401)
                ->push(['e_bill' => ['bill_id' => 'B2']], 201),
        ]);

        $res = (new EBillingService())->createBill(['amount' => 2000]);

        $this->assertTrue($res['success']);
        $this->assertSame('B2', $res['bill_id']);
        // Le 2e appel e-billing doit porter l'auth Basic (base64 user:sharedkey)
        Http::assertSent(fn ($r) => str_contains($r->url(), 'ebill.test')
            && $r->hasHeader('Authorization', 'Basic ' . base64_encode('user:sharedkey')));
    }

    public function test_push_ussd_uses_v2_endpoint_with_bearer(): void
    {
        $this->setEnv([
            'EBILLING_AUTH_MODE' => 'oauth',
            'EBILLING_SERVER_URL' => 'https://ebill.test/api/v1/merchant/e_bills',
        ]);
        Http::fake([
            'cognito.test/*' => Http::response(['access_token' => 'BEARER123', 'expires_in' => 3600], 200),
            'ebill.test/*' => Http::response(['state' => 'ready'], 200),
        ]);

        (new EBillingService())->pushUSSD('INV-1', 'SIMU', '077000001');

        Http::assertSent(fn ($r) => str_contains($r->url(), '/api/v2/merchant/e_bills/INV-1/ussd_push')
            && $r->hasHeader('Authorization', 'Bearer BEARER123'));
    }

    public function test_basic_mode_uses_basic_and_no_token_call(): void
    {
        $this->setEnv(['EBILLING_AUTH_MODE' => 'basic']);
        Http::fake([
            'ebill.test/*' => Http::response(['e_bill' => ['bill_id' => 'B3']], 201),
            'cognito.test/*' => Http::response([], 500), // ne doit pas être appelé
        ]);

        $res = (new EBillingService())->createBill(['amount' => 3000]);

        $this->assertTrue($res['success']);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'cognito.test'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'ebill.test')
            && $r->hasHeader('Authorization', 'Basic ' . base64_encode('user:sharedkey')));
    }
}
