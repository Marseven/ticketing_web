<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pour les requêtes API, retourner JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter.'
                ], 401);
            }

            $user = Auth::user();

            // Vérifier si l'utilisateur peut accéder à l'admin
            if (!$user->canAccessAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires pour accéder à l\'administration.'
                ], 403);
            }

            return $next($request);
        }

        // Pour les requêtes web (fallback, normalement pas utilisé dans une SPA)
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $user = Auth::user();

        if (!$user->canAccessAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        return $next($request);
    }
}