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

        $metadata = $user->metadata ?? [];
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_file ? '/storage/images/users/' . $user->avatar_file : $user->avatar_url,
                'bio' => $metadata['bio'] ?? null,
                'city' => $metadata['city'] ?? null,
                'country' => $metadata['country'] ?? null,
                'birthdate' => $metadata['birthdate'] ?? null,
                'language' => $metadata['language'] ?? 'fr',
                'email_notifications' => $metadata['email_notifications'] ?? true,
                'sms_notifications' => $metadata['sms_notifications'] ?? true,
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
                'avatar_url' => $user->avatar_file ? '/storage/images/users/' . $user->avatar_file : $user->avatar_url,
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

    /**
     * Get client recent activities
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        $user = $request->user();
        $activities = [];

        // Récupérer les tickets récents
        $recentTickets = $user->tickets()
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentTickets as $ticket) {
            $activities[] = [
                'id' => 'ticket_' . $ticket->id,
                'type' => 'ticket_purchase',
                'title' => 'Ticket acheté pour "' . $ticket->event->title . '"',
                'date' => $this->formatActivityDate($ticket->created_at),
                'icon' => 'TicketIcon',
                'created_at' => $ticket->created_at
            ];
        }

        // Récupérer les commandes récentes
        $recentOrders = $user->orders()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($recentOrders as $order) {
            $activities[] = [
                'id' => 'order_' . $order->id,
                'type' => 'order_completed',
                'title' => 'Achat confirmé - ' . $order->tickets_count . ' ticket(s)',
                'date' => $this->formatActivityDate($order->created_at),
                'icon' => 'CheckCircleIcon',
                'created_at' => $order->created_at
            ];
        }

        // Activité de mise à jour du profil
        if ($user->updated_at > $user->created_at) {
            $activities[] = [
                'id' => 'profile_update',
                'type' => 'profile_update',
                'title' => 'Profil mis à jour',
                'date' => $this->formatActivityDate($user->updated_at),
                'icon' => 'UserIcon',
                'created_at' => $user->updated_at
            ];
        }

        // Trier par date décroissante et limiter à 5
        usort($activities, function($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        $activities = array_slice($activities, 0, 5);

        // Nettoyer les created_at pour la réponse
        foreach ($activities as &$activity) {
            unset($activity['created_at']);
        }

        return response()->json([
            'activities' => $activities
        ]);
    }

    /**
     * Update client password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Données invalides',
                'errors' => ['current_password' => ['Le mot de passe actuel est incorrect.']]
            ], 422);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès'
        ]);
    }

    /**
     * Delete client account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:SUPPRIMER']
        ]);

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect',
                'errors' => ['password' => ['Le mot de passe est incorrect.']]
            ], 422);
        }

        // Supprimer l'utilisateur (soft delete si configuré)
        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }

    /**
     * Update client preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'email_notifications' => ['boolean'],
            'sms_notifications' => ['boolean'],
            'language' => ['string', 'in:fr,en']
        ]);

        $metadata = $user->metadata ?? [];
        
        if ($request->has('email_notifications')) {
            $metadata['email_notifications'] = $request->boolean('email_notifications');
        }
        
        if ($request->has('sms_notifications')) {
            $metadata['sms_notifications'] = $request->boolean('sms_notifications');
        }
        
        if ($request->has('language')) {
            $metadata['language'] = $request->language;
        }

        $user->update(['metadata' => $metadata]);

        return response()->json([
            'message' => 'Préférences mises à jour avec succès',
            'preferences' => [
                'email_notifications' => $metadata['email_notifications'] ?? true,
                'sms_notifications' => $metadata['sms_notifications'] ?? true,
                'language' => $metadata['language'] ?? 'fr'
            ]
        ]);
    }

    /**
     * Format activity date for display
     */
    private function formatActivityDate($date): string
    {
        $diffInMinutes = now()->diffInMinutes($date);
        $diffInHours = now()->diffInHours($date);
        $diffInDays = now()->diffInDays($date);
        
        // Si c'est dans le futur ou très récent (moins de 5 minutes)
        if ($diffInMinutes < 5) {
            return "À l'instant";
        } elseif ($diffInMinutes < 60) {
            return "Il y a {$diffInMinutes} minute" . ($diffInMinutes > 1 ? 's' : '');
        } elseif ($diffInHours < 24) {
            return "Il y a {$diffInHours} heure" . ($diffInHours > 1 ? 's' : '');
        } elseif ($diffInDays === 1) {
            return "Hier";
        } elseif ($diffInDays < 7) {
            return "Il y a {$diffInDays} jour" . ($diffInDays > 1 ? 's' : '');
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return $weeks === 1 ? "Il y a 1 semaine" : "Il y a {$weeks} semaines";
        } else {
            $months = floor($diffInDays / 30);
            return $months === 1 ? "Il y a 1 mois" : "Il y a {$months} mois";
        }
    }
}