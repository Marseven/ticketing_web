<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ClientController extends Controller
{
    /**
     * Get client profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Statistiques utilisateur
        $stats = [
            'total_orders' => $user->orders()->count(),
            'total_tickets' => $user->tickets()->count(),
            'upcoming_events' => $user->tickets()
                ->whereHas('event.schedules', function ($query) {
                    $query->where('starts_at', '>', now());
                })
                ->count(),
            'total_spent' => $user->orders()
                ->where('status', 'completed')
                ->sum('total_amount')
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->getImageUrl(),
                'created_at' => $user->created_at->format('d/m/Y H:i:s'),
                'updated_at' => $user->updated_at->format('d/m/Y H:i:s'),
            ],
            'stats' => $stats
        ]);
    }

    /**
     * Update client profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:2'],
            'birthdate' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'in:fr,en'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Vérifier le mot de passe actuel si un nouveau mot de passe est fourni
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Données invalides',
                    'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']]
                ], 422);
            }
        }

        // Mettre à jour les informations
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Champs optionnels stockés dans les métadonnées ou colonnes dédiées
        $metadata = $user->metadata ?? [];
        
        if ($request->has('bio')) {
            $metadata['bio'] = $request->bio;
        }
        
        if ($request->has('city')) {
            $metadata['city'] = $request->city;
        }
        
        if ($request->has('country')) {
            $metadata['country'] = $request->country;
        }
        
        if ($request->has('birthdate')) {
            $metadata['birthdate'] = $request->birthdate;
        }
        
        if ($request->has('language')) {
            $metadata['language'] = $request->language;
        }

        $updateData['metadata'] = $metadata;

        $user->update($updateData);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->getImageUrl(),
                'bio' => $metadata['bio'] ?? null,
                'city' => $metadata['city'] ?? null,
                'country' => $metadata['country'] ?? null,
                'birthdate' => $metadata['birthdate'] ?? null,
                'language' => $metadata['language'] ?? 'fr',
                'updated_at' => $user->updated_at->format('d/m/Y H:i:s'),
            ],
            'message' => 'Profil mis à jour avec succès'
        ]);
    }

    /**
     * Upload client avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            
            // Utiliser la même logique que les événements pour le stockage
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('images/users', $filename, 'public');
            
            // Supprimer l'ancien avatar s'il existe
            if ($user->avatar_file) {
                $oldPath = storage_path('app/public/images/users/' . $user->avatar_file);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Mettre à jour l'utilisateur
            $user->update([
                'avatar_file' => $filename,
                'avatar_url' => null // Reset URL pour forcer l'utilisation du fichier local
            ]);

            return response()->json([
                'message' => 'Avatar mis à jour avec succès',
                'avatar_url' => $user->getImageUrl()
            ]);
        }

        return response()->json([
            'message' => 'Aucun fichier fourni'
        ], 400);
    }
}