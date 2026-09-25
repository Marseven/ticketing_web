<?php

use Illuminate\Http\Request;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance aux reverse proxies pour récupérer la vraie IP
        // client. Sans ça, $request->ip() renvoie l'IP du proxy Hostinger,
        // ce qui casse le filtre IP du webhook E-Billing.
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'organizer' => \App\Http\Middleware\OrganizerMiddleware::class,
            'admin.access' => \App\Http\Middleware\AdminAccess::class,
            'superadmin.access' => \App\Http\Middleware\SuperAdminAccess::class,
            'organizer.access' => \App\Http\Middleware\OrganizerAccess::class,
            'client.access' => \App\Http\Middleware\ClientAccess::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);

        // Les routes API s'authentifient par token Bearer (Sanctum), pas par
        // cookie de session : elles n'ont pas besoin de protection CSRF. On les
        // exempte toutes (sinon les requêtes du SPA en fetch sans X-XSRF-TOKEN
        // échouent en "CSRF token mismatch" sur les domaines stateful).
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Gérer les utilisateurs non authentifiés pour les routes API
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Retourner null pour les requêtes API (génère une réponse 401)
            }
            return route('spa'); // Rediriger vers la SPA pour les autres requêtes
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Une route d'API répond toujours en JSON, même à un navigateur.
        //
        // ⚠️ Sans cela, une requête non authentifiée qui accepte du HTML — une
        // URL collée dans la barre d'adresse, ou un bouton qui navigue au lieu
        // de récupérer — faisait tenter à Laravel une redirection vers
        // `route('login')`. Cette route n'existe pas dans cette application :
        // l'exception remontait et le serveur rendait une erreur 500 là où un
        // 401 s'imposait.
        //
        // Le symptôme trompait doublement : un administrateur lisait
        // « 500 Server Error » sur l'URL d'un export et concluait que l'export
        // était cassé, alors qu'il lui manquait seulement un jeton — et le
        // journal ne parlait que d'une route de connexion introuvable, sans
        // jamais nommer la fonctionnalité.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
