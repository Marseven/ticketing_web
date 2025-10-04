<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    /**
     * Demander une réinitialisation de mot de passe
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            
            // Supprimer les anciens tokens pour cet email
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            // Générer un nouveau token
            $token = Str::random(64);
            
            // Enregistrer le token
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]);

            // Envoyer l'email
            $user->notify(new \App\Notifications\PasswordResetNotification($token, false));

            return response()->json([
                'success' => true,
                'message' => 'Un email de réinitialisation a été envoyé à votre adresse email.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur forgot password', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email de réinitialisation.'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        try {
            // Vérifier le token
            $tokenData = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            // Comparer les tokens hashés avec SHA256
            if (!$tokenData || hash('sha256', $request->token) !== $tokenData->token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré.'
                ], 422);
            }

            // Vérifier si le token n'est pas expiré (60 minutes)
            if (now()->diffInMinutes($tokenData->created_at) > 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le token a expiré. Veuillez demander un nouveau lien.'
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user = User::where('email', $request->email)->first();
            $user->password = bcrypt($request->password);
            $user->save();

            // Supprimer le token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès.',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur reset password', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation du mot de passe.'
            ], 500);
        }
    }

    /**
     * Vérifier la validité d'un token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Comparer les tokens hashés avec SHA256
        if (!$tokenData || hash('sha256', $request->token) !== $tokenData->token) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide.'
            ], 422);
        }

        // Vérifier si le token n'est pas expiré (60 minutes)
        if (now()->diffInMinutes($tokenData->created_at) > 60) {
            return response()->json([
                'success' => false,
                'message' => 'Token expiré.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token valide.',
        ]);
    }
}
