<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Empreinte d'un visiteur, sans le pister.
 *
 * Compter des visiteurs suppose de reconnaître deux consultations comme
 * venant de la même personne. On ne veut pour autant ni cookie, ni adresse IP
 * conservée.
 *
 * L'empreinte mélange l'adresse, le navigateur, la clé de l'application et
 * **la date du jour**. Elle change donc à minuit : impossible de suivre
 * quelqu'un d'un jour sur l'autre, et impossible de remonter à une personne
 * à partir de la valeur stockée. On mesure des visites, pas des gens.
 */
class VisitorFingerprint
{
    public function for(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            (string) $request->userAgent(),
            config('app.key'),
            now()->toDateString(),
        ]));
    }

    /** « mobile », « tablet » ou « desktop », d'après le navigateur annoncé. */
    public function device(Request $request): string
    {
        $agent = strtolower((string) $request->userAgent());

        if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($agent, 'mobi') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Provenance lisible : « whatsapp », « facebook », « google », le domaine
     * s'il est inconnu, ou « direct ». Le chemin complet n'est pas conservé,
     * il n'apprendrait rien de plus et peut contenir des identifiants.
     */
    public function referrerSource(?string $referrer, string $ownHost): ?string
    {
        if (! $referrer) {
            return 'direct';
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));

        if ($host === '' || $host === strtolower($ownHost)) {
            return 'direct';
        }

        foreach (['whatsapp' => 'whatsapp', 'facebook' => 'facebook', 'fb.' => 'facebook',
                  'instagram' => 'instagram', 'google' => 'google', 'tiktok' => 'tiktok',
                  't.co' => 'twitter', 'twitter' => 'twitter', 'x.com' => 'twitter',
                  'linkedin' => 'linkedin', 'youtube' => 'youtube', 'bing' => 'bing'] as $needle => $label) {
            if (str_contains($host, $needle)) {
                return $label;
            }
        }

        return preg_replace('/^www\./', '', $host) ?: 'direct';
    }

    /**
     * Robot ? On ne compte pas les moteurs dans la fréquentation, sinon les
     * chiffres ne veulent plus rien dire.
     */
    public function looksLikeBot(?string $userAgent): bool
    {
        $agent = strtolower((string) $userAgent);

        if ($agent === '') {
            return true;
        }

        foreach (['bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview',
                  'headless', 'monitor', 'curl', 'wget', 'python-requests', 'postman'] as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
