<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20|unique:users',
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

        \DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_organizer' => $request->is_organizer ?? false,
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

                // Créer le balance pour l'organisateur
                \App\Models\OrganizerBalance::create([
                    'organizer_id' => $organizer->id,
                    'balance' => 0,
                    'pending_balance' => 0,
                    'auto_payout_enabled' => false,
                ]);

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
            'login' => 'required|string', // Peut être email ou téléphone
            'password' => 'required|string',
        ], [
            'login.required' => 'L\'email ou le téléphone est obligatoire',
            'password.required' => 'Le mot de passe est obligatoire',
        ]);

        // Déterminer si le login est un email ou un téléphone
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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
        if (str_contains($userAgent, 'TicketingMobile') && !$user->is_organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les organisateurs peuvent utiliser l\'application mobile.',
            ], 403);
        }

        // Supprimer les anciens tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Charger les rôles
        $userData = $user->load('roles');

        // Déterminer le type d'utilisateur
        $isAdmin = $userData->roles->contains('slug', 'admin');
        $isOrganizer = $userData->roles->contains('slug', 'organizer') || $userData->is_organizer;

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
        $user = $request->user()->load('roles');
        
        // Déterminer le type d'utilisateur
        $isAdmin = $user->roles->contains('slug', 'admin');
        $isOrganizer = $user->roles->contains('slug', 'organizer') || $user->is_organizer;
        
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
