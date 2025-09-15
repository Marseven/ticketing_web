<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action, string $module): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                           ->with('error', 'Vous devez vous connecter pour accéder à cette page.');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur peut effectuer l'action sur le module
        if (!$user->can($action, $module)) {
            abort(403, "Accès refusé. Vous n'avez pas la permission de {$action} dans le module {$module}.");
        }

        return $next($request);
    }
}