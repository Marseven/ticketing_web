<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les sessions ouvertes d'un compte — c'est-à-dire ses jetons Sanctum.
 *
 * L'écran de profil proposait déjà « révoquer » et « déconnecter partout »,
 * mais aucune route ne répondait : l'interface annonçait un succès sans que
 * rien ne soit révoqué. Un administrateur qui soupçonne une intrusion
 * croyait donc avoir fermé les accès en les laissant grands ouverts — c'est
 * exactement la situation où l'on a le moins le droit de mentir.
 */
class SessionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken();

        $sessions = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'current' => $current && $token->id === $current->id,
            ]);

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    /**
     * Révoquer une session précise.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        // Filtrer par la relation de l'utilisateur, jamais par l'identifiant
        // seul : autrement, n'importe qui révoquerait la session d'autrui en
        // changeant un chiffre dans l'URL.
        $token = $request->user()->tokens()->whereKey($id)->first();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Session introuvable.',
            ], 404);
        }

        $current = $request->user()->currentAccessToken();

        if ($current && $token->id === $current->id) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisez la déconnexion pour fermer la session courante.',
                'error_code' => 'CURRENT_SESSION',
            ], 422);
        }

        $token->delete();

        return response()->json(['success' => true, 'message' => 'Session révoquée.']);
    }

    /**
     * Fermer toutes les autres sessions, en gardant celle d'où l'on agit.
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken();

        $revoked = $request->user()->tokens()
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->delete();

        return response()->json([
            'success' => true,
            'data' => ['revoked' => $revoked],
            'message' => $revoked === 0
                ? 'Aucune autre session ouverte.'
                : "{$revoked} session(s) fermée(s).",
        ]);
    }
}
