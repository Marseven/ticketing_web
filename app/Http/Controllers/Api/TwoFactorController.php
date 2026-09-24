<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Activation et retrait de la double authentification (Google Authenticator).
 *
 * Le parcours en trois temps est délibéré :
 *   1. `store`   génère un secret et l'affiche en QR — le 2FA n'est PAS actif.
 *   2. `confirm` exige un premier code valide, et c'est seulement là qu'il
 *      s'active, avec les codes de secours rendus une seule fois.
 *   3. `destroy` le retire, contre le mot de passe.
 *
 * Activer dès l'étape 1 enfermerait dehors quiconque scanne mal le QR, se
 * trompe de compte ou change de téléphone en cours de route : il faudrait
 * alors une intervention en base pour le laisser revenir.
 */
class TwoFactorController extends Controller
{
    public function __construct(private TotpService $totp)
    {
    }

    /** Où en est l'utilisateur ? */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $user->hasTwoFactorEnabled(),
                'pending' => ! $user->hasTwoFactorEnabled() && filled($user->two_factor_secret),
                'confirmed_at' => $user->two_factor_confirmed_at,
                'recovery_codes_left' => count($user->two_factor_recovery_codes ?? []),
            ],
        ]);
    }

    /**
     * Étape 1 — proposer un secret et son QR code.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'La double authentification est déjà active.',
                'error_code' => 'ALREADY_ENABLED',
            ], 422);
        }

        // Un secret neuf à chaque tentative : si l'utilisateur recommence, le
        // précédent QR — peut-être resté affiché sur un écran partagé — ne
        // vaut plus rien.
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        // Le nom affiché dans l'application d'authentification vient de la
        // MARQUE configurée, pas de `APP_NAME` : cette variable
        // d'environnement sert au serveur (journaux, files, e-mails
        // techniques) et peut porter tout autre chose. C'est elle qui faisait
        // apparaître un nom de marque abandonné dans Google Authenticator.
        $uri = $this->totp->provisioningUri(
            $secret,
            $user->email ?: (string) $user->phone,
            Setting::branding()['app_name'] ?? 'Primea'
        );

        return response()->json([
            'success' => true,
            'data' => [
                'secret' => $secret,
                'otpauth_uri' => $uri,
                'qr_svg' => $this->qr($uri),
            ],
            'message' => 'Scannez ce QR code, puis saisissez le code affiché pour activer.',
        ]);
    }

    /**
     * Étape 2 — un premier code valide prouve que le téléphone est configuré.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(
            ['code' => 'required|string|max:32'],
            ['code.required' => 'Saisissez le code affiché par votre application.']
        );

        $user = $request->user();

        if (blank($user->two_factor_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Commencez par générer un QR code.',
                'error_code' => 'NO_PENDING_SECRET',
            ], 422);
        }

        if (! $this->totp->verify($user->two_factor_secret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Code incorrect. Vérifiez l\'heure de votre téléphone.',
                'error_code' => 'INVALID_CODE',
            ], 422);
        }

        $codes = $this->recoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            // Rendus une seule fois : ils ne sont pas réaffichables ensuite.
            'data' => ['recovery_codes' => $codes],
            'message' => 'Double authentification activée. Conservez ces codes de secours.',
        ]);
    }

    /**
     * Étape 3 — retirer le second facteur, contre le mot de passe.
     *
     * Sans cette preuve, un jeton volé suffirait à désarmer la protection
     * censée le rendre inoffensif.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(
            ['password' => 'required|string'],
            ['password.required' => 'Confirmez votre mot de passe pour désactiver.']
        );

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Double authentification désactivée.',
        ]);
    }

    /**
     * Refaire une série de codes de secours, contre le mot de passe.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'La double authentification n\'est pas active.',
                'error_code' => 'NOT_ENABLED',
            ], 422);
        }

        $codes = $this->recoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return response()->json([
            'success' => true,
            'data' => ['recovery_codes' => $codes],
            'message' => 'Anciens codes de secours annulés.',
        ]);
    }

    /** @return array<int, string> */
    private function recoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }

    /**
     * Le QR est rendu côté serveur : le secret n'a alors pas à traverser une
     * bibliothèque tierce chargée dans le navigateur.
     */
    private function qr(string $uri): ?string
    {
        try {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(220)->margin(1)->generate($uri);
        } catch (\Throwable $e) {
            // L'URI suffit à configurer l'application à la main : une image
            // manquante ne doit pas empêcher d'activer le 2FA.
            return null;
        }
    }
}
