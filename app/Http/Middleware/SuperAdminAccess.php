<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route aux super administrateurs.
 *
 * La supervision expose l'état technique de la plateforme, sa fréquentation
 * et les flux financiers en cours. Le refus est explicite — « réservé aux
 * super administrateurs » — parce qu'un administrateur ordinaire qui tombe
 * sur un 403 muet croirait à une panne.
 */
class SuperAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise.',
            ], 401);
        }

        if (! $user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Réservé aux super administrateurs.',
            ], 403);
        }

        return $next($request);
    }
}
