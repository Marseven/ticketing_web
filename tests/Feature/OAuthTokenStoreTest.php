<?php

namespace Tests\Feature;

use App\Services\OAuthTokenStore;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Le token OAuth est mis en cache et réutilisé tant qu'il est valide ; une
 * fois oublié (forget), il est régénéré via la fonction de récupération.
 */
class OAuthTokenStoreTest extends TestCase
{
    public function test_token_is_cached_then_regenerated_after_forget(): void
    {
        Cache::flush();
        $calls = 0;
        $fetch = function () use (&$calls) {
            $calls++;
            return ['access_token' => 'tok' . $calls, 'expires_in' => 3600];
        };

        $first = OAuthTokenStore::token('k_test', $fetch);
        $second = OAuthTokenStore::token('k_test', $fetch); // depuis le cache

        $this->assertSame('tok1', $first);
        $this->assertSame('tok1', $second);
        $this->assertSame(1, $calls, 'Le token en cache ne doit pas être re-récupéré');

        OAuthTokenStore::forget('k_test');
        $third = OAuthTokenStore::token('k_test', $fetch); // régénéré

        $this->assertSame('tok2', $third);
        $this->assertSame(2, $calls);
    }

    public function test_missing_access_token_throws(): void
    {
        Cache::flush();
        $this->expectException(\RuntimeException::class);
        OAuthTokenStore::token('k_bad', fn () => ['expires_in' => 3600]);
    }
}
