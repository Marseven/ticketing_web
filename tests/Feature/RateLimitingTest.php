<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 0 perf/sécurité — les limiteurs de débit nommés sont bien branchés :
 * au-delà du quota, l'API répond 429 au lieu de laisser passer un flood.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_10_attempts_per_minute(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $res = $this->postJson('/api/v1/auth/login', ['login' => 'nobody@x.test', 'password' => 'wrong']);
            $this->assertNotSame(429, $res->getStatusCode(), "La tentative #$i ne doit pas être bloquée");
        }

        $this->postJson('/api/v1/auth/login', ['login' => 'nobody@x.test', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_ticket_lookup_is_throttled_after_30_requests_per_minute(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $res = $this->getJson('/api/v1/track/some-token-that-does-not-exist');
            $this->assertNotSame(429, $res->getStatusCode(), "La requête #$i ne doit pas être bloquée");
        }

        $this->getJson('/api/v1/track/some-token-that-does-not-exist')->assertStatus(429);
    }
}
