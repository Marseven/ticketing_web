<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Phase 0 perf/sécurité — limiteurs de débit nommés (utilisés via
     * `throttle:<nom>` dans routes/api.php). Ils protègent les chemins
     * coûteux ou sensibles contre les floods et l'énumération, sans gêner
     * un usage réel :
     *  - auth     : 10/min par IP   (login, inscription, mot de passe oublié)
     *  - payments : 10/min par IP   (initiation paiement, USSD, commandes invité)
     *  - lookup   : 30/min par IP   (recherche/récupération de billet, suivi public)
     *  - scan     : 180/min par agent (validation + sync scans mobile ; un
     *               agent scanne ≤ ~60/min → 3× de marge, bloque les boucles)
     * Réponse 429 « Too Many Requests » au-delà.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('payments', fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('lookup', fn (Request $request) =>
            Limit::perMinute(30)->by($request->ip())
        );

        RateLimiter::for('scan', fn (Request $request) =>
            Limit::perMinute(180)->by($request->user()?->id ?: $request->ip())
        );
    }
}
