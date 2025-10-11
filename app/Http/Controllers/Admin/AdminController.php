<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organizer;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\OrganizerBalance;
use App\Models\Category;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Admin dashboard avec statistiques complètes
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            // Statistiques générales
            $ticketsVendus = DB::table('tickets')
                ->whereIn('status', ['issued', 'used'])
                ->count();

            $ticketsScannes = DB::table('tickets')
                ->where('status', 'used')
                ->count();

            $montantGlobal = Payment::where('status', 'success')
                ->sum('amount');

            $revenusTotal = Payment::where('status', 'success')
                ->sum('amount');

            $stats = [
                'total_events' => Event::count(),
                'montant_global' => $montantGlobal,
                'tickets_vendus' => $ticketsVendus,
                'tickets_scannes' => $ticketsScannes,
                'revenus_total' => $revenusTotal,

                // Stats additionnelles pour compatibilité
                'total_users' => User::count(),
                'total_organizers' => Organizer::count(),
                'total_balance' => OrganizerBalance::sum('balance'),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'failed_payments' => Payment::where('status', 'failed')
                    ->whereDate('created_at', today())
                    ->count(),
            ];

            // Commandes récentes
            $recentOrders = Order::with(['event', 'organizer'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Activité récente (simulée pour l'exemple)
            $recentActivity = collect([
                [
                    'id' => 1,
                    'type' => 'order',
                    'description' => 'Nouvelle commande #' . $recentOrders->first()?->reference,
                    'created_at' => now()->subMinutes(5),
                ],
                [
                    'id' => 2,
                    'type' => 'payment',
                    'description' => 'Paiement de ' . number_format(15000) . ' XAF reçu',
                    'created_at' => now()->subMinutes(15),
                ],
                [
                    'id' => 3,
                    'type' => 'user',
                    'description' => 'Nouvel utilisateur inscrit',
                    'created_at' => now()->subHours(1),
                ],
                [
                    'id' => 4,
                    'type' => 'payout',
                    'description' => 'Payout de ' . number_format(25000) . ' XAF traité',
                    'created_at' => now()->subHours(2),
                ],
            ]);

            // Données de revenus des 7 derniers jours
            $revenueData = Payment::where('status', 'success')
                ->where('created_at', '>=', now()->subDays(7))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as amount')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top événements
            $topEvents = Event::with(['organizer', 'tickets.ticketType'])
                ->get()
                ->map(function ($event) {
                    $soldTickets = $event->tickets->whereIn('status', ['issued', 'used']);
                    $ticketsSold = $soldTickets->count();
                    $revenue = $soldTickets->sum(function ($ticket) {
                        return $ticket->ticketType ? $ticket->ticketType->price : 0;
                    });

                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'organizer_name' => $event->organizer ? $event->organizer->name : 'N/A',
                        'tickets_sold' => $ticketsSold,
                        'revenue' => $revenue,
                        'cover_image' => $event->cover_image_url,
                    ];
                })
                ->sortByDesc('tickets_sold')
                ->take(5)
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_orders' => $recentOrders,
                    'recent_activity' => $recentActivity,
                    'revenue_data' => $revenueData,
                    'top_events' => $topEvents,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dashboard admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement du dashboard'
            ], 500);
        }
    }

    /**
     * Gestion des utilisateurs - Liste avec filtres
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('role')) {
                if ($request->role === 'admin') {
                    $query->whereHas('roles', function ($q) {
                        $q->where('slug', \App\Models\Role::ADMIN);
                    });
                } elseif ($request->role === 'organizer') {
                    $query->whereHas('roles', function ($q) {
                        $q->where('slug', \App\Models\Role::ORGANIZER);
                    });
                } elseif ($request->role === 'client') {
                    $query->whereHas('roles', function ($q) {
                        $q->where('slug', \App\Models\Role::CLIENT);
                    });
                }
            }

            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('status', 'active');
                } elseif ($request->status === 'inactive') {
                    $query->where('status', '!=', 'active');
                }
            }

            $users = $query->withCount(['organizers'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => ['users' => $users]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste utilisateurs admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des utilisateurs'
            ], 500);
        }
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'is_admin' => 'boolean',
            'is_organizer' => 'boolean',
            'avatar_url' => 'nullable|url',
            'avatar_file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Créer l'utilisateur sans mot de passe
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt(\Str::random(32)), // Mot de passe temporaire aléatoire
                'is_organizer' => $request->boolean('is_organizer'),
                'email_verified_at' => now(),
                'avatar_url' => $request->avatar_url,
                'avatar_file' => $request->avatar_file,
            ]);

            // Assigner les rôles
            if ($request->boolean('is_admin')) {
                $user->assignRole(\App\Models\Role::ADMIN);
            }
            
            if ($request->boolean('is_organizer')) {
                $user->assignRole(\App\Models\Role::ORGANIZER);
            }
            
            // Si aucun rôle spécifique, assigner le rôle client
            if (!$request->boolean('is_admin') && !$request->boolean('is_organizer')) {
                $user->assignRole(\App\Models\Role::CLIENT);
            }

            // Générer un token de réinitialisation
            $token = \Str::random(64);
            
            // Enregistrer le token
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]);

            // Envoyer l'email de bienvenue avec lien de réinitialisation
            $user->notify(new \App\Notifications\PasswordResetNotification($token, true));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès. Un email lui a été envoyé pour définir son mot de passe.',
                'data' => ['user' => $user->load('roles')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création utilisateur admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'is_admin' => 'sometimes|boolean',
            'is_organizer' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'avatar_url' => 'nullable|url',
            'avatar_file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = $request->only(['name', 'email', 'is_organizer', 'avatar_url', 'avatar_file']);

            // Gestion activation/désactivation
            if ($request->has('is_active')) {
                $updateData['status'] = $request->boolean('is_active') ? 'active' : 'inactive';
            }

            $user->update($updateData);

            // Gestion des rôles
            if ($request->has('is_admin') || $request->has('is_organizer')) {
                // Supprimer tous les rôles existants
                $user->roles()->detach();
                
                // Assigner les nouveaux rôles
                if ($request->boolean('is_admin')) {
                    $user->assignRole(\App\Models\Role::ADMIN);
                }
                
                if ($request->boolean('is_organizer')) {
                    $user->assignRole(\App\Models\Role::ORGANIZER);
                }
                
                // Si aucun rôle spécifique, assigner le rôle client
                if (!$request->boolean('is_admin') && !$request->boolean('is_organizer')) {
                    $user->assignRole(\App\Models\Role::CLIENT);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => ['user' => $user->fresh()->load('roles')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour utilisateur admin', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUserStatus(User $user): JsonResponse
    {
        try {
            if ($user->status === 'active') {
                $user->status = 'inactive';
                $message = 'Utilisateur désactivé avec succès';
            } else {
                $user->status = 'active';
                $message = 'Utilisateur activé avec succès';
            }
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['user' => $user->fresh()->load('roles')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur toggle statut utilisateur', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du changement de statut'
            ], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetUserPassword(User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Générer un nouveau token de réinitialisation
            $token = \Str::random(64);
            
            // Supprimer les anciens tokens pour cet email
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();
            
            // Enregistrer le nouveau token
            DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]);

            // Envoyer l'email de réinitialisation
            $user->notify(new \App\Notifications\PasswordResetNotification($token, false));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Email de réinitialisation envoyé à l\'utilisateur.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur réinitialisation mot de passe admin', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de l\'envoi de l\'email'
            ], 500);
        }
    }

    /**
     * Gestion des organisateurs - Liste avec filtres
     */
    public function organizers(Request $request): JsonResponse
    {
        try {
            $query = Organizer::with(['users']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('verified')) {
                if ($request->boolean('verified')) {
                    $query->where('is_active', true);
                } else {
                    $query->where('is_active', false);
                }
            }

            $organizers = $query->withCount(['events'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => ['organizers' => $organizers]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste organisateurs admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des organisateurs'
            ], 500);
        }
    }

    /**
     * Créer un organisateur
     */
    public function createOrganizer(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $organizer = Organizer::create([
                'name' => $request->name,
                'bio' => $request->bio,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'status' => $request->status,
                'is_active' => $request->status === 'active',
            ]);

            // Associer les utilisateurs
            if ($request->filled('user_ids')) {
                $organizer->users()->attach($request->user_ids, [
                    'role' => 'admin',
                    'permissions' => json_encode(['manage_events', 'view_analytics']),
                ]);

                // Marquer les utilisateurs comme organisateurs
                User::whereIn('id', $request->user_ids)->update(['is_organizer' => true]);
            }

            // Créer les balances par défaut
            foreach (['airtelmoney', 'moovmoney'] as $gateway) {
                OrganizerBalance::create([
                    'organizer_id' => $organizer->id,
                    'gateway' => $gateway,
                    'balance' => 0,
                    'auto_payout_enabled' => false,
                    'auto_payout_threshold' => 10000,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Organisateur créé avec succès',
                'data' => ['organizer' => $organizer->load('users')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création organisateur admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour un organisateur
     */
    public function updateOrganizer(Request $request, $organizerId): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'bio' => 'nullable|string',
            'contact_email' => 'sometimes|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Trouver l'organisateur avec gestion d'erreur
            $organizer = Organizer::find($organizerId);
            if (!$organizer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Organisateur introuvable'
                ], 404);
            }

            $updateData = $request->only([
                'name', 'bio', 'contact_email', 
                'contact_phone', 'status'
            ]);

            // Gérer le statut actif
            if ($request->filled('status')) {
                $updateData['is_active'] = $request->status === 'active';
            }

            $organizer->update($updateData);

            // Mettre à jour les associations utilisateurs
            if ($request->has('user_ids')) {
                // Retirer le statut organisateur des anciens utilisateurs
                $oldUserIds = $organizer->users->pluck('id')->toArray();
                User::whereIn('id', $oldUserIds)->update(['is_organizer' => false]);

                // Mettre à jour les associations
                $organizer->users()->sync($request->user_ids);

                // Marquer les nouveaux utilisateurs comme organisateurs
                if (!empty($request->user_ids)) {
                    User::whereIn('id', $request->user_ids)->update(['is_organizer' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Organisateur mis à jour avec succès',
                'data' => ['organizer' => $organizer->fresh()->load('users')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour organisateur admin', [
                'organizer_id' => $organizer->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Gestion des événements - Liste avec filtres
     */
    public function events(Request $request): JsonResponse
    {
        try {
            $query = Event::with(['organizer', 'category', 'venue', 'ticketTypes']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('organizer_id')) {
                $query->where('organizer_id', $request->organizer_id);
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $events = $query->withCount(['tickets as tickets_sold' => function ($q) {
                    $q->whereIn('status', ['issued', 'used']);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => ['events' => $events]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste événements admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des événements'
            ], 500);
        }
    }

    /**
     * Créer un événement
     */
    public function createEvent(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'organizer_id' => 'required|exists:organizers,id',
            'category_id' => 'required|exists:event_categories,id',
            'venue_id' => 'nullable|exists:venues,id',
            'new_venue_name' => 'nullable|string|max:255',
            'new_venue_city' => 'nullable|string|max:255',
            'new_venue_address' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,cancelled',
            'is_active' => 'boolean',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.starts_at' => 'required|date',
            'schedules.*.ends_at' => 'required|date|after:schedules.*.starts_at',
            'ticket_types' => 'nullable|array',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.capacity' => 'required|integer|min:1',
            'ticket_types.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $venueId = $request->venue_id;

            // Créer un nouveau lieu si nécessaire
            if ($request->venue_id === 'new' && $request->filled('new_venue_name')) {
                $venue = Venue::create([
                    'name' => $request->new_venue_name,
                    'city' => $request->new_venue_city,
                    'address' => $request->new_venue_address,
                ]);
                $venueId = $venue->id;
            }

            // Créer l'événement
            $event = Event::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'description' => $request->description,
                'organizer_id' => $request->organizer_id,
                'category_id' => $request->category_id,
                'venue_id' => $venueId,
                'status' => $request->status,
                'is_active' => $request->boolean('is_active', true),
                'image_url' => $request->image_url,
                'image_file' => $request->image_file,
            ]);

            // Créer les horaires
            if ($request->filled('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $event->schedules()->create([
                        'starts_at' => $schedule['starts_at'],
                        'ends_at' => $schedule['ends_at'],
                        'status' => 'active',
                    ]);
                }
            }

            // Créer les types de billets
            if ($request->filled('ticket_types')) {
                foreach ($request->ticket_types as $ticketType) {
                    $event->ticketTypes()->create([
                        'name' => $ticketType['name'],
                        'price' => $ticketType['price'],
                        'available_quantity' => $ticketType['capacity'],
                        'max_quantity' => $ticketType['capacity'],
                        'description' => $ticketType['description'] ?? null,
                        'status' => 'active',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès',
                'data' => ['event' => $event->load(['organizer', 'category', 'venue', 'schedules', 'ticketTypes'])]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création événement admin', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Afficher un événement spécifique
     */
    public function showEvent(Request $request, $eventId): JsonResponse
    {
        try {
            $event = Event::with(['organizer', 'category', 'venue', 'schedules', 'ticketTypes'])
                ->find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Événement introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['event' => $event]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage événement admin', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement de l\'événement'
            ], 500);
        }
    }

    /**
     * Mettre à jour un événement
     */
    public function updateEvent(Request $request, $eventId): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'organizer_id' => 'sometimes|exists:organizers,id',
            'category_id' => 'sometimes|exists:event_categories,id',
            'venue_id' => 'nullable|exists:venues,id',
            'new_venue_name' => 'nullable|string|max:255',
            'new_venue_city' => 'nullable|string|max:255',
            'new_venue_address' => 'nullable|string|max:255',
            'status' => 'sometimes|in:draft,published,cancelled',
            'is_active' => 'sometimes|boolean',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.starts_at' => 'required|date',
            'schedules.*.ends_at' => 'required|date|after:schedules.*.starts_at',
            'ticket_types' => 'nullable|array',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.capacity' => 'required|integer|min:1',
            'ticket_types.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $event = Event::find($eventId);
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Événement introuvable'
                ], 404);
            }

            $venueId = $request->venue_id;

            // Créer un nouveau lieu si nécessaire
            if ($request->venue_id === 'new' && $request->filled('new_venue_name')) {
                $venue = Venue::create([
                    'name' => $request->new_venue_name,
                    'city' => $request->new_venue_city,
                    'address' => $request->new_venue_address,
                ]);
                $venueId = $venue->id;
            }

            // Mettre à jour l'événement
            $updateData = $request->only([
                'title', 'description', 'organizer_id', 
                'category_id', 'status', 'is_active',
                'image_url', 'image_file'
            ]);
            
            if ($request->filled('title')) {
                $updateData['slug'] = Str::slug($request->title);
            }
            
            if ($venueId) {
                $updateData['venue_id'] = $venueId;
            }

            $event->update($updateData);

            // Mettre à jour les horaires
            if ($request->has('schedules')) {
                $event->schedules()->delete();
                if (!empty($request->schedules)) {
                    foreach ($request->schedules as $schedule) {
                        $event->schedules()->create([
                            'starts_at' => $schedule['starts_at'],
                            'ends_at' => $schedule['ends_at'],
                            'status' => 'active',
                        ]);
                    }
                }
            }

            // Mettre à jour les types de billets
            if ($request->has('ticket_types')) {
                $event->ticketTypes()->delete();
                if (!empty($request->ticket_types)) {
                    foreach ($request->ticket_types as $ticketType) {
                        $event->ticketTypes()->create([
                            'name' => $ticketType['name'],
                            'price' => $ticketType['price'],
                            'available_quantity' => $ticketType['capacity'],
                            'max_quantity' => $ticketType['capacity'],
                            'description' => $ticketType['description'] ?? null,
                            'status' => 'active',
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Événement mis à jour avec succès',
                'data' => ['event' => $event->fresh()->load(['organizer', 'category', 'venue', 'schedules', 'ticketTypes'])]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour événement admin', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Gestion des catégories - Liste avec filtres
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $query = Category::withCount(['events' => function ($query) {
                $query->where('status', 'published');
            }]);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            $categories = $query->orderBy('name')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $categories,
                'categories' => $categories // Pour compatibilité
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste catégories admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des catégories'
            ], 500);
        }
    }

    /**
     * Créer une catégorie
     */
    public function createCategory(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:event_categories,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'color' => $request->color ?? '#272d63',
                'is_active' => $request->boolean('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => ['category' => $category]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur création catégorie admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(Request $request, $categoryId): JsonResponse
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable'
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:event_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['name', 'description', 'color']);
            
            if ($request->filled('name')) {
                $updateData['slug'] = Str::slug($request->name);
            }
            
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->boolean('is_active');
            }

            $category->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => ['category' => $category->fresh()->loadCount('events')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour catégorie admin', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory($categoryId): JsonResponse
    {
        try {
            $category = Category::withCount('events')->find($categoryId);
            
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie introuvable'
                ], 404);
            }

            // Vérifier s'il y a des événements associés
            if ($category->events_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette catégorie car elle contient des événements'
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression catégorie admin', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression'
            ], 500);
        }
    }

    /**
     * Gestion des lieux - Liste avec filtres
     */
    public function venues(Request $request): JsonResponse
    {
        try {
            $query = Venue::with(['organizer'])
                ->withCount('events');

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('city')) {
                $query->where('city', $request->city);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $venues = $query->orderBy('city')
                ->orderBy('name')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $venues,
                'venues' => $venues // Pour compatibilité
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste lieux admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des lieux'
            ], 500);
        }
    }

    /**
     * Créer un lieu
     */
    public function createVenue(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'image' => 'nullable|string',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'geo_lat' => 'nullable|numeric|between:-90,90',
            'geo_lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Récupérer le premier organisateur par défaut
            $organizerId = $request->organizer_id ?? Organizer::first()?->id;
            
            if (!$organizerId) {
                // Créer un organisateur par défaut si aucun n'existe
                $defaultOrganizer = Organizer::create([
                    'name' => 'Organisateur Principal',
                    'slug' => 'organisateur-principal',
                    'contact_email' => 'contact@primea.ga',
                    'contact_phone' => '+24100000000',
                    'status' => 'active',
                    'is_active' => true,
                ]);
                $organizerId = $defaultOrganizer->id;
            }

            $venue = Venue::create([
                'organizer_id' => $organizerId,
                'name' => $request->name,
                'description' => $request->description,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'Gabon',
                'capacity' => $request->capacity,
                'phone' => $request->phone,
                'email' => $request->email,
                'image' => $request->image,
                'image_url' => $request->image_url,
                'image_file' => $request->image_file,
                'status' => $request->status ?? 'active',
                'geo_lat' => $request->geo_lat,
                'geo_lng' => $request->geo_lng,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lieu créé avec succès',
                'data' => ['venue' => $venue->load('organizer')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur création lieu admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour un lieu
     */
    public function updateVenue(Request $request, $venueId): JsonResponse
    {
        $venue = Venue::find($venueId);
        if (!$venue) {
            return response()->json([
                'success' => false,
                'message' => 'Lieu introuvable'
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'image' => 'nullable|string',
            'image_url' => 'nullable|url',
            'image_file' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'geo_lat' => 'nullable|numeric|between:-90,90',
            'geo_lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $venue->update($request->only([
                'name', 'description', 'address', 'city', 'postal_code',
                'country', 'capacity', 'phone', 'email', 'image', 'status',
                'image_url', 'image_file',
                'geo_lat', 'geo_lng'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Lieu mis à jour avec succès',
                'data' => ['venue' => $venue->fresh()->load('organizer')->loadCount('events')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour lieu admin', [
                'venue_id' => $venueId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer un lieu
     */
    public function deleteVenue($venueId): JsonResponse
    {
        try {
            $venue = Venue::withCount('events')->find($venueId);
            
            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lieu introuvable'
                ], 404);
            }

            // Vérifier s'il y a des événements associés
            if ($venue->events_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce lieu car il contient des événements'
                ], 400);
            }

            $venue->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lieu supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression lieu admin', [
                'venue_id' => $venueId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression'
            ], 500);
        }
    }
}