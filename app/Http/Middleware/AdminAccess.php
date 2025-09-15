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
        if (!Auth::check()) {
            return redirect()->route('login')
                           ->with('error', 'Vous devez vous connecter pour accéder à cette page.');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur peut accéder à l'admin
        if (!$user->canAccessAdmin()) {
            abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires pour accéder à l\'administration.');
        }

        return $next($request);
    }
}