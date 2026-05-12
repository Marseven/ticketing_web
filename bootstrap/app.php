<?php

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
            'organizer.access' => \App\Http\Middleware\OrganizerAccess::class,
            'client.access' => \App\Http\Middleware\ClientAccess::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);

        // Utiliser notre middleware CSRF personnalisé pour exempter certaines routes
        $middleware->validateCsrfTokens(except: [
            'api/register',
            'api/login',
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
        //
    })->create();
