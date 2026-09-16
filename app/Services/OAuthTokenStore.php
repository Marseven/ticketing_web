<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Stockage + régénération des tokens OAuth (dépôt e-billing / PA payout).
 *
 * Principe : le token est mis en cache et réutilisé tant qu'il est valide ;
 * il est régénéré AVANT expiration (marge de sécurité). Chaque intégration
 * fournit sa propre fonction de récupération et sa clé de cache.
 */
class OAuthTokenStore
{
    /** Marge (secondes) avant expiration pour régénérer le token. */
    private const EXPIRY_MARGIN = 60;

    /**
     * Retourne un token valide depuis le cache, ou le régénère via $fetch.
     *
     * @param  string   $cacheKey  Clé de cache (une par intégration).
     * @param  callable $fetch     Doit retourner ['access_token' => string, 'expires_in' => int].
     */
    public static function token(string $cacheKey, callable $fetch): string
    {
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $data = $fetch();
        $token = $data['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('Token OAuth absent de la réponse d\'authentification.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $ttl = max(30, $expiresIn - self::EXPIRY_MARGIN);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /** Invalider un token en cache (ex : après un 401, pour forcer la régénération). */
    public static function forget(string $cacheKey): void
    {
        Cache::forget($cacheKey);
    }
}
