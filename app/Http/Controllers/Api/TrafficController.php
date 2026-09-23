<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Services\VisitorFingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Signalement d'une page consultée.
 *
 * L'application est une SPA : le serveur ne voit qu'un chargement par visite,
 * alors que la personne parcourt plusieurs écrans. Le navigateur signale donc
 * lui-même chaque changement d'écran.
 *
 * Ce point d'entrée est public — il doit l'être, la fréquentation vient
 * surtout de visiteurs non connectés — donc il n'écrit que ce qu'il fabrique
 * lui-même : jamais une valeur envoyée par le client autre que le chemin, et
 * ce chemin est tronqué et nettoyé.
 */
class TrafficController extends Controller
{
    public function store(Request $request, VisitorFingerprint $fingerprint): JsonResponse
    {
        // Un robot ne fait pas partie de la fréquentation : le compter
        // rendrait les chiffres illisibles.
        if ($fingerprint->looksLikeBot($request->userAgent())) {
            return response()->json(['success' => true, 'counted' => false]);
        }

        $validated = $request->validate([
            'path' => 'required|string|max:512',
            'referrer' => 'nullable|string|max:2048',
        ]);

        try {
            PageView::create([
                'path' => $this->cleanPath($validated['path']),
                'visitor_hash' => $fingerprint->for($request),
                'referrer_source' => $fingerprint->referrerSource(
                    $validated['referrer'] ?? null,
                    (string) $request->getHost()
                ),
                'device' => $fingerprint->device($request),
                'user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            // La mesure d'audience ne doit jamais gêner la navigation : en cas
            // de souci on perd une ligne de statistiques, rien de plus.
            return response()->json(['success' => true, 'counted' => false]);
        }

        return response()->json(['success' => true, 'counted' => true]);
    }

    /**
     * Garde le chemin, jette le reste.
     *
     * La chaîne de requête peut porter un jeton de récupération de billet ou
     * une référence de commande : elle n'a rien à faire dans un journal de
     * fréquentation.
     */
    private function cleanPath(string $path): string
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        return mb_substr($path, 0, 512);
    }
}
