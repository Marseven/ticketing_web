<?php

namespace App\Http\Controllers\Api;

use App\Rules\PhoneNumberRule;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\TotpService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="is_organizer", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="is_organizer", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_token..."),
     *             @OA\Property(property="message", type="string", example="Inscription réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:255|confirmed',
            'phone' => ['required', 'string', 'max:20', 'unique:users', new PhoneNumberRule()],
            'is_organizer' => 'nullable|boolean',
            'organization_name' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Le nom complet est obligatoire',
            'name.min' => 'Le nom doit contenir au moins 2 caractères',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'Les mots de passe ne correspondent pas',
            'phone.required' => 'Le numéro de téléphone est obligatoire',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé',
        ]);

        // Normaliser le numéro de téléphone au format international +241XXXXXXXX
        $phone = $request->phone;
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) === 8) {
            // Format local sans indicatif : 07XXXXXX → +24107XXXXXX
            $phone = '+241' . $digits;
        } elseif (strlen($digits) === 9 && str_starts_with($digits, '0')) {
            // Format local avec 0 : 007XXXXXX → +24107XXXXXX
            $phone = '+241' . $digits;
        } elseif (strlen($digits) >= 11 && str_starts_with($digits, '241')) {
            // Format 241XXXXXXXX → +241XXXXXXXX
            $phone = '+' . $digits;
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+' . $digits;
        }

        \DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $phone,
                // L'inscription autonome comme organisateur est fermée par
                // défaut : le drapeau envoyé par le formulaire est ignoré et
                // le compte créé est un compte client. Masquer les liens ne
                // suffisait pas, l'inscription étant un appel d'API public.
                // Voir config/features.php pour rouvrir.
                'is_organizer' => config('features.organizer_self_signup', false)
                    ? (bool) ($request->is_organizer ?? false)
                    : false,
                'status' => 'active',
                'email_verified_at' => null, // L'email n'est pas encore vérifié
            ]);

            // Si l'utilisateur est un organisateur, créer automatiquement son organisation
            if ($user->is_organizer) {
                $organizerName = $request->organization_name ?? $request->name;

                $organizer = \App\Models\Organizer::create([
                    'name' => $organizerName,
                    'slug' => \Illuminate\Support\Str::slug($organizerName . '-' . time()),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'description' => null,
                    'is_active' => true,
                    'is_verified' => true, // Pas de vérification admin requise
                ]);

                // Associer l'utilisateur à l'organisation comme owner
                $organizer->users()->attach($user->id, ['role' => 'owner']);

                // Assigner le rôle 'organizer' au user
                $organizerRole = \App\Models\Role::where('slug', \App\Models\Role::ORGANIZER)->first();
                if ($organizerRole && !$user->hasRole($organizerRole->slug)) {
                    $user->roles()->attach($organizerRole->id, [
                        'assigned_at' => now(),
                        'assigned_by' => null,
                    ]);

                    \Log::info('Rôle organisateur assigné lors de l\'inscription', [
                        'user_id' => $user->id,
                        'role_id' => $organizerRole->id
                    ]);
                } else if (!$organizerRole) {
                    \Log::warning('Rôle organisateur non trouvé dans la table roles', [
                        'user_id' => $user->id
                    ]);
                }

                // Note: Le balance sera créé quand l'organisateur configurera ses moyens de paiement
                // Un organisateur peut avoir plusieurs balances (un par gateway: airtelmoney, moovmoney, etc.)

                \Log::info('Organisation créée automatiquement lors de l\'inscription', [
                    'user_id' => $user->id,
                    'organizer_id' => $organizer->id,
                    'organizer_name' => $organizer->name
                ]);
            }

            // Envoyer l'email de vérification
            $user->sendEmailVerificationNotification();

            // Créer un token pour l'utilisateur non vérifié
            $token = $user->createToken('auth_token')->plainTextToken;

            \DB::commit();

            $message = $user->is_organizer
                ? 'Inscription réussie. Votre compte organisateur a été créé. Un email de confirmation a été envoyé.'
                : 'Inscription réussie. Un email de vérification a été envoyé.';

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_organizer' => $user->is_organizer,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
                'message' => $message,
                'email_verification_required' => true,
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Erreur lors de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'inscription',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="is_organizer", type="boolean", example=false)
     *             ),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_token..."),
     *             @OA\Property(property="message", type="string", example="Connexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account inactive or organizer-only access"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string|max:255', // Peut être email ou téléphone
            'password' => 'required|string|max:255',
        ], [
            'login.required' => 'L\'email ou le téléphone est obligatoire',
            'password.required' => 'Le mot de passe est obligatoire',
        ]);

        // Déterminer si le login est un email ou un téléphone
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        $user = User::where($loginField, $request->login)->first();

        $passwordOk = $user && Hash::check($request->password, $user->password);

        // Fallback comptes migrés depuis le legacy Primea (mot de passe MD5) :
        // si le bcrypt échoue mais que le MD5 legacy correspond, on rehash en
        // bcrypt de façon transparente et on efface le MD5 (migration au vol).
        if ($user && !$passwordOk && !empty($user->legacy_md5)
            && hash_equals((string) $user->legacy_md5, md5($request->password))) {
            $user->password = Hash::make($request->password);
            $user->legacy_md5 = null;
            $user->save();
            $passwordOk = true;
        }

        if (!$passwordOk) {
            throw ValidationException::withMessages([
                'login' => ['Les informations de connexion sont incorrectes.'],
            ]);
        }

        // Vérifier si l'utilisateur est actif
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est inactif ou suspendu.',
            ], 403);
        }

        // Pour l'app mobile, vérifier si c'est un organisateur
        $userAgent = $request->header('User-Agent');
        if (str_contains($userAgent, 'TicketingMobile') && !$user->canScanTickets()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Réservé aux organisateurs, à leur personnel et aux administrateurs.',
            ], 403);
        }

        // Double authentification : le mot de passe ne suffit plus.
        //
        // AUCUN jeton n'est émis ici. On rend un défi de courte durée, qui
        // n'ouvre rien par lui-même : sans le code, la session n'existe pas.
        // Émettre le jeton puis « demander » le code laisserait un accès
        // complet à qui intercepterait la première réponse.
        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => true,
                'two_factor_required' => true,
                'challenge' => $this->startTwoFactorChallenge($user),
                'message' => 'Saisissez le code affiché par votre application d\'authentification.',
            ]);
        }

        return $this->issueSession($user);
    }

    /**
     * Ouvrir la session : jeton et profil.
     *
     * Partagé par la connexion simple et par la validation du second facteur,
     * pour que les deux chemins délivrent exactement la même chose — deux
     * copies auraient fini par diverger, et c'est le genre d'écart qui donne
     * des droits à un endroit et pas à l'autre.
     */
    private function issueSession(User $user): JsonResponse
    {
        // Supprimer les anciens tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Charger les rôles et le user type
        $userData = $user->load(['roles', 'userType']);

        // Administrateur par son TYPE **ou** par son RÔLE. Ne regarder que le
        // type faisait apparaître « Client » à un administrateur dont le rôle
        // était posé sans le type — et il perdait l'accès à l'administration,
        // alors que le serveur l'y autorisait. `isPlatformAdmin()` est la
        // définition que le reste de l'application utilise.
        $isAdmin = $userData->isPlatformAdmin();
        $isOrganizer = $userData->is_organizer;

        // Ajouter les propriétés calculées
        $userData->is_admin = $isAdmin;
        $userData->is_organizer = $isOrganizer;

        // Vérifier si l'email est vérifié (pour afficher un message, mais ne pas bloquer)
        $emailVerified = $user->hasVerifiedEmail();
        $message = $emailVerified
            ? 'Connexion réussie'
            : 'Connexion réussie. N\'oubliez pas de vérifier votre adresse email.';

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_file ? '/storage/images/users/' . $user->avatar_file : $user->avatar_url,
                'is_organizer' => $userData->is_organizer,
                'is_admin' => $userData->is_admin,
                'can_scan' => $user->canScanTickets(),
                // Ouvre la supervision de la plateforme. Le menu s'en sert
                // pour n'afficher l'entrée qu'aux personnes concernées ; le
                // contrôle qui compte reste côté serveur.
                'is_super_admin' => $user->isSuperAdmin(),
                'roles' => $userData->roles,
                'active_tickets_count' => 0, // TODO: calculer
                'email_verified_at' => $user->email_verified_at,
            ],
            'token' => $token,
            'message' => $message,
            'access_level' => $isAdmin ? 'admin' : ($isOrganizer ? 'organizer' : 'client'),
            'email_verification_required' => !$emailVerified,
        ]);
    }

    /** Clé de cache d'un défi de second facteur. */
    private function challengeKey(string $challenge): string
    {
        return 'two_factor_challenge:' . hash('sha256', $challenge);
    }

    /**
     * Ouvrir un défi : cinq minutes pour saisir le code, pas davantage.
     */
    private function startTwoFactorChallenge(User $user): string
    {
        $challenge = Str::random(64);

        Cache::put($this->challengeKey($challenge), [
            'user_id' => $user->id,
            'attempts' => 0,
        ], now()->addMinutes(5));

        return $challenge;
    }

    /**
     * Second facteur : valider le code et ouvrir enfin la session.
     *
     * Six chiffres se devinent vite si l'on peut essayer sans fin. Le défi est
     * donc détruit au bout de cinq essais — l'utilisateur légitime refait une
     * connexion, l'attaquant repart de zéro à chaque fois et n'accumule rien.
     */
    public function twoFactorChallenge(Request $request, TotpService $totp): JsonResponse
    {
        $request->validate([
            'challenge' => 'required|string|max:128',
            'code' => 'required_without:recovery_code|nullable|string|max:32',
            'recovery_code' => 'required_without:code|nullable|string|max:32',
        ], [
            'challenge.required' => 'Session de connexion expirée, recommencez.',
            'code.required_without' => 'Saisissez le code de votre application ou un code de secours.',
        ]);

        $key = $this->challengeKey($request->challenge);
        $state = Cache::get($key);
        $user = $state ? User::find($state['user_id']) : null;

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Session de connexion expirée. Reconnectez-vous.',
                'error_code' => 'CHALLENGE_EXPIRED',
            ], 422);
        }

        if (filled($request->code) && $totp->verify($user->two_factor_secret, $request->code)) {
            Cache::forget($key);

            return $this->issueSession($user);
        }

        if (filled($request->recovery_code) && $this->consumeRecoveryCode($user, $request->recovery_code)) {
            Cache::forget($key);

            return $this->issueSession($user);
        }

        // Essai manqué : on rapproche le défi de sa fin.
        $state['attempts']++;

        if ($state['attempts'] >= 5) {
            Cache::forget($key);

            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Reconnectez-vous.',
                'error_code' => 'CHALLENGE_BURNED',
            ], 429);
        }

        Cache::put($key, $state, now()->addMinutes(5));

        return response()->json([
            'success' => false,
            'message' => 'Code incorrect.',
            'error_code' => 'INVALID_CODE',
            'attempts_left' => 5 - $state['attempts'],
        ], 422);
    }

    /**
     * Un code de secours ne sert qu'une fois — sinon ce n'est plus un secours,
     * c'est un mot de passe permanent noté sur un bout de papier.
     */
    private function consumeRecoveryCode(User $user, string $provided): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $provided = trim($provided);
        $match = null;

        // Parcours complet et comparaison à temps constant : s'arrêter au bon
        // code rendrait sa position mesurable.
        foreach ($codes as $code) {
            if (hash_equals($code, $provided)) {
                $match = $code;
            }
        }

        if ($match === null) {
            return false;
        }

        $user->two_factor_recovery_codes = array_values(array_filter(
            $codes,
            fn ($code) => ! hash_equals($code, $match)
        ));
        $user->save();

        return true;
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     summary="Get authenticated user information",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="is_organizer", type="boolean", example=false),
     *                 @OA\Property(property="organizers", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'userType']);

        // Même définition qu'à la connexion : type OU rôle.
        $isAdmin = $user->isPlatformAdmin();
        $isOrganizer = $user->is_organizer;
        
        // Ajouter les propriétés calculées
        $user->is_admin = $isAdmin;
        $user->is_organizer = $isOrganizer;
        $user->active_tickets_count = 0; // TODO: calculer le vrai nombre de billets actifs

        // Charger les organisateurs associés si c'est un organisateur
        if ($user->is_organizer) {
            $user->load('organizers');
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_file ? '/storage/images/users/' . $user->avatar_file : $user->avatar_url,
            'is_organizer' => $user->is_organizer,
            'is_admin' => $user->is_admin,
            // Même drapeau qu'à la connexion : le menu de supervision ne
            // s'affiche que pour les super administrateurs.
            'is_super_admin' => $user->isSuperAdmin(),
            'active_tickets_count' => $user->active_tickets_count,
            'roles' => $user->roles,
            'organizers' => $user->is_organizer ? $user->organizers : [],
        ]);
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'message' => 'Token rafraîchi avec succès',
        ]);
    }

    /**
     * Vérifier l'email avec le lien de vérification
     */
    public function verifyEmail(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user) {
            return redirect('/email-verification-result?status=error&message=' . urlencode('Utilisateur non trouvé'));
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect('/email-verification-result?status=error&message=' . urlencode('Lien de vérification invalide'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/email-verification-result?status=info&message=' . urlencode('Email déjà vérifié'));
        }

        if ($user->markEmailAsVerified()) {
            // Optionnel: dispatch d'un événement
            // event(new Verified($user));
        }

        return redirect('/email-verification-result?status=success&message=' . urlencode('Email vérifié avec succès'));
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email déjà vérifié'
            ], 400);
        }

        try {
            $user->sendEmailVerificationNotification();
            
            \Log::info('Email de vérification renvoyé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'time' => now()
            ]);

            return response()->json([
                'message' => 'Email de vérification renvoyé'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du renvoi de l\'email de vérification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Vérifier le statut de vérification d'email
     */
    public function checkEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
            'needs_verification' => !$user->hasVerifiedEmail(),
        ]);
    }
}
