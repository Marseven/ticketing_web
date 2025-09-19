<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier si l'utilisateur est admin
        // Vous pouvez ajuster cette logique selon votre système d'utilisateurs
        if (!$this->isAdmin($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé - Droits administrateur requis'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Vérifier si l'utilisateur est administrateur
     */
    private function isAdmin($user): bool
    {
        // Option 1: Vérifier par email (temporaire)
        $adminEmails = [
            'admin@primea.ga',
            'contact@digitech-africa.com',
        ];

        if (in_array($user->email, $adminEmails)) {
            return true;
        }

        // Option 2: Vérifier par role si vous avez un système de rôles
        if (isset($user->role) && $user->role === 'admin') {
            return true;
        }

        // Option 3: Vérifier par une colonne is_admin
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return false;
    }
}