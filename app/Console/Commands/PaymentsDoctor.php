<?php

namespace App\Console\Commands;

use App\Exceptions\PaymentGatewayUnavailable;
use App\Services\EBillingService;
use Illuminate\Console\Command;

/**
 * État de santé de la configuration des paiements.
 *
 * Deux pannes de paiement en production ont eu la même cause : des
 * identifiants absents de la configuration effective, sans que rien ne le dise
 * avant qu'un client ne bute sur la page d'achat. Cette commande donne la
 * réponse en une ligne, depuis le serveur concerné.
 *
 * Elle n'affiche jamais une valeur secrète : seulement présente ou absente, et
 * les quatre derniers caractères pour lever un doute d'accent ou d'espace.
 */
class PaymentsDoctor extends Command
{
    protected $signature = 'payments:doctor
        {--live : demander un vrai jeton à la passerelle pour prouver que les identifiants sont acceptés}';

    protected $description = 'Vérifie que les identifiants de paiement arrivent bien jusqu\'à l\'application';

    public function handle(): int
    {
        $this->configCacheState();

        $ok = $this->section('E-billing (encaissement)', [
            'EBILLING_USERNAME' => config('services.ebilling.username'),
            'EBILLING_SHARED_KEY' => config('services.ebilling.shared_key'),
            'EBILLING_SERVER_URL' => config('services.ebilling.server_url'),
            'EBILLING_POST_URL' => config('services.ebilling.post_url'),
        ]);

        $mode = strtolower((string) config('services.ebilling.auth_mode', 'oauth'));
        $this->line("  Mode d'authentification : {$mode}");

        if ($mode === 'oauth') {
            // Sans identifiants Cognito, le service retombe sur Basic — qui
            // fonctionne encore. C'est un avertissement, pas une panne : le
            // diagnostic ne doit pas afficher du rouge sur un paiement qui
            // marche, sinon plus personne ne le croit.
            $oauthComplete = $this->section('E-billing OAuth (Cognito)', [
                'EBILLING_OAUTH_TOKEN_URL' => config('services.ebilling.oauth_token_url'),
                'EBILLING_OAUTH_CLIENT_ID' => config('services.ebilling.oauth_client_id'),
                'EBILLING_OAUTH_CLIENT_SECRET' => config('services.ebilling.oauth_client_secret'),
            ]);

            if (! $oauthComplete) {
                $this->line('  <fg=yellow>!</> Repli sur l\'authentification Basic, que billing-easy a annoncé refuser.');
                $this->line('     Le paiement peut fonctionner aujourd\'hui et s\'arrêter sans prévenir.');
            }
        }

        $this->section('SHAP (versements aux organisateurs)', [
            'API_PAYOUT_ID' => config('services.shap.api_id'),
            'API_PAYOUT_SECRET' => config('services.shap.api_secret'),
            'SHAP_BASE_URL' => config('services.shap.base_url'),
        ]);

        $ok = $this->serviceBoots() && $ok;

        if ($this->option('live')) {
            $ok = $this->liveAuth() && $ok;
        }

        $this->newLine();

        if ($ok) {
            $this->info('✓ La configuration de paiement est complète.');

            if (! $this->option('live')) {
                $this->line('  Pour vérifier que la passerelle accepte ces identifiants : payments:doctor --live');
            }

            return self::SUCCESS;
        }

        $this->error('✗ Le paiement ne peut pas fonctionner en l\'état.');
        $this->line('  Compléter le .env, puis : php artisan optimize:clear && php artisan optimize');

        return self::FAILURE;
    }

    /**
     * La mise en cache de la configuration est le piège classique : `env()`
     * renvoie null dès qu'elle est active, hors des fichiers de config.
     */
    private function configCacheState(): void
    {
        $cached = file_exists($this->laravel->getCachedConfigPath());

        $this->newLine();
        $this->line('Environnement : ' . config('app.env') . ' · Configuration en cache : ' . ($cached ? 'oui' : 'non'));

        if ($cached) {
            $this->line('  Un .env modifié depuis le dernier `optimize` n\'est pas encore pris en compte.');
        }
    }

    /**
     * @param  array<string, mixed>  $keys
     */
    private function section(string $title, array $keys): bool
    {
        $this->newLine();
        $this->line($title);

        $complete = true;

        foreach ($keys as $name => $value) {
            $value = is_string($value) ? trim($value) : $value;

            if ($value === null || $value === '') {
                $this->line("  <fg=red>absente</>  {$name}");
                $complete = false;
                continue;
            }

            $this->line("  <fg=green>présente</> {$name} <fg=gray>(…" . $this->tail((string) $value) . ')</>');
        }

        return $complete;
    }

    /** Fin de valeur, assez pour lever un doute sans rien révéler. */
    private function tail(string $value): string
    {
        return strlen($value) <= 8
            ? str_repeat('•', strlen($value))
            : substr($value, -4);
    }

    private function serviceBoots(): bool
    {
        $this->newLine();

        try {
            app(EBillingService::class);
            $this->line('  <fg=green>✓</> Le service e-billing démarre.');

            return true;
        } catch (PaymentGatewayUnavailable $e) {
            $this->line('  <fg=red>✗</> ' . $e->getMessage());
            $this->line('     Message vu par le client : ' . $e->publicMessage());

            return false;
        }
    }

    /** Preuve par l'appel : la passerelle délivre-t-elle un jeton ? */
    private function liveAuth(): bool
    {
        $this->newLine();
        $this->line('Appel réel à la passerelle…');

        try {
            $result = app(EBillingService::class)->authCheck();
        } catch (\Throwable $e) {
            $this->line('  <fg=red>✗</> ' . $e->getMessage());

            return false;
        }

        if ($result['ok']) {
            $this->line("  <fg=green>✓</> Identifiants acceptés ({$result['detail']}).");

            return true;
        }

        $this->line("  <fg=red>✗</> Identifiants refusés : {$result['detail']}");

        return false;
    }
}
