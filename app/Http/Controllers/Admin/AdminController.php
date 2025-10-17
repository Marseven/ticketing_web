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
use App\Models\UserType;
use App\Models\Privilege;
use App\Models\Role;
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

            // Calculer les paiements par statut
            $successfulPayments = Payment::where('status', 'success')->count();
            $pendingPayments = Payment::where('status', 'pending')->count();
            $failedPayments = Payment::where('status', 'failed')->count();

            // Calculer la commission plateforme (par exemple 5% du total)
            $platformCommission = $revenusTotal * 0.05;

            // Compter les événements actifs (événements à venir)
            $activeEvents = Event::where('status', 'published')
                ->whereHas('schedules', function ($q) {
                    $q->where('starts_at', '>', now());
                })
                ->count();

            // Compter les utilisateurs actifs (connectés dans les 30 derniers jours)
            $activeUsers = User::where('last_login_at', '>=', now()->subDays(30))
                ->orWhere('created_at', '>=', now()->subDays(30))
                ->count();

            $stats = [
                // Principales stats
                'total_revenue' => $revenusTotal,
                'tickets_sold' => $ticketsVendus,
                'active_events' => $activeEvents ?: Event::where('status', 'published')->count(),
                'active_users' => $activeUsers ?: User::count(),

                // Stats paiements
                'successful_payments' => $successfulPayments,
                'pending_payments' => $pendingPayments,
                'failed_payments' => $failedPayments,
                'platform_commission' => $platformCommission,

                // Stats additionnelles pour compatibilité
                'total_events' => Event::count(),
                'total_users' => User::count(),
                'total_organizers' => Organizer::count(),
                'total_balance' => OrganizerBalance::sum('balance'),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'revenue_today' => Payment::where('status', 'success')
                    ->whereDate('created_at', today())
                    ->sum('amount'),
                'tickets_scannes' => $ticketsScannes,

                // Anciennes clés pour rétro-compatibilité
                'montant_global' => $montantGlobal,
                'tickets_vendus' => $ticketsVendus,
                'revenus_total' => $revenusTotal,
            ];

            // Commandes récentes avec mapping des champs
            $recentOrders = Order::with(['event', 'organizer', 'buyer'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'reference' => $order->reference,
                        'customer_name' => $order->guest_name ?? ($order->buyer ? $order->buyer->name : 'N/A'),
                        'customer_email' => $order->guest_email ?? ($order->buyer ? $order->buyer->email : 'N/A'),
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'event' => $order->event ? [
                            'id' => $order->event->id,
                            'title' => $order->event->title,
                        ] : null,
                    ];
                });

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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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

            // Gérer l'upload de l'image
            $imageFile = null;
            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('events', $filename, 'public');
                $imageFile = $path;
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
                'image_file' => $imageFile,
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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

            // Gérer l'upload de la nouvelle image
            if ($request->hasFile('cover_image')) {
                // Supprimer l'ancienne image si elle existe
                if ($event->image_file && \Storage::disk('public')->exists($event->image_file)) {
                    \Storage::disk('public')->delete($event->image_file);
                }

                $file = $request->file('cover_image');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('events', $filename, 'public');
                $event->image_file = $path;
            }

            // Mettre à jour l'événement
            $updateData = $request->only([
                'title', 'description', 'organizer_id',
                'category_id', 'status', 'is_active', 'image_url'
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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

            // Gérer l'upload de l'image
            $imageFile = null;
            if ($request->hasFile('cover_image')) {
                $file = $request->file('cover_image');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('venues', $filename, 'public');
                $imageFile = $path;
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
                'image_file' => $imageFile,
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
            // Gérer l'upload de la nouvelle image
            if ($request->hasFile('cover_image')) {
                // Supprimer l'ancienne image si elle existe
                if ($venue->image_file && \Storage::disk('public')->exists($venue->image_file)) {
                    \Storage::disk('public')->delete($venue->image_file);
                }

                $file = $request->file('cover_image');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('venues', $filename, 'public');
                $venue->image_file = $path;
            }

            $venue->update($request->only([
                'name', 'description', 'address', 'city', 'postal_code',
                'country', 'capacity', 'phone', 'email', 'image', 'status',
                'image_url',
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

    // ===================================================================
    // GESTION DES TYPES D'UTILISATEURS (lecture seule)
    // ===================================================================

    /**
     * Liste des types d'utilisateurs
     */
    public function userTypes(Request $request): JsonResponse
    {
        try {
            $query = UserType::with(['users']);

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            $userTypes = $query->withCount('users')
                ->orderBy('name')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $userTypes
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste types utilisateurs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des types d\'utilisateurs'
            ], 500);
        }
    }

    /**
     * Afficher un type d'utilisateur
     */
    public function showUserType($userTypeId): JsonResponse
    {
        try {
            $userType = UserType::with(['users'])->withCount('users')->find($userTypeId);

            if (!$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type d\'utilisateur introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['userType' => $userType]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage type utilisateur', [
                'user_type_id' => $userTypeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    // ===================================================================
    // GESTION DES PRIVILÈGES (lecture seule)
    // ===================================================================

    /**
     * Liste des privilèges
     */
    public function privileges(Request $request): JsonResponse
    {
        try {
            $query = Privilege::with(['roles']);

            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            $privileges = $query->withCount('roles')
                ->orderBy('module')
                ->orderBy('action')
                ->paginate($request->per_page ?? 50);

            return response()->json([
                'success' => true,
                'data' => $privileges
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste privilèges', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des privilèges'
            ], 500);
        }
    }

    /**
     * Afficher un privilège
     */
    public function showPrivilege($privilegeId): JsonResponse
    {
        try {
            $privilege = Privilege::with(['roles'])->withCount('roles')->find($privilegeId);

            if (!$privilege) {
                return response()->json([
                    'success' => false,
                    'message' => 'Privilège introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['privilege' => $privilege]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage privilège', [
                'privilege_id' => $privilegeId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    // ===================================================================
    // GESTION DES RÔLES
    // ===================================================================

    /**
     * Liste des rôles
     */
    public function roles(Request $request): JsonResponse
    {
        try {
            $query = Role::with(['users', 'privileges', 'userType']);

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Filtrer par user_type_id si fourni
            if ($request->filled('user_type_id')) {
                $query->where('user_type_id', $request->user_type_id);
            }

            $roles = $query->withCount(['users', 'privileges'])
                ->orderBy('level', 'desc')
                ->orderBy('name')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste rôles', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des rôles'
            ], 500);
        }
    }

    /**
     * Afficher un rôle
     */
    public function showRole($roleId): JsonResponse
    {
        try {
            $role = Role::with(['users', 'privileges'])
                ->withCount(['users', 'privileges'])
                ->find($roleId);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rôle introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['role' => $role]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage rôle', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Créer un rôle (uniquement pour les rôles de type admin/custom)
     */
    public function createRole(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'type' => 'required|in:custom', // Seulement custom, pas system
            'level' => 'required|integer|min:0|max:100',
            'privilege_ids' => 'nullable|array',
            'privilege_ids.*' => 'exists:privileges,id',
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

            $role = Role::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'type' => $request->type,
                'level' => $request->level,
                'is_active' => true,
            ]);

            // Assigner les privilèges
            if ($request->filled('privilege_ids')) {
                $role->privileges()->sync($request->privilege_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rôle créé avec succès',
                'data' => ['role' => $role->load('privileges')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création rôle', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la création'
            ], 500);
        }
    }

    /**
     * Mettre à jour un rôle
     */
    public function updateRole(Request $request, $roleId): JsonResponse
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle introuvable'
            ], 404);
        }

        // Empêcher la modification des rôles système
        if ($role->type === Role::TYPE_SYSTEM) {
            return response()->json([
                'success' => false,
                'message' => 'Les rôles système ne peuvent pas être modifiés'
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'level' => 'sometimes|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'privilege_ids' => 'nullable|array',
            'privilege_ids.*' => 'exists:privileges,id',
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

            $updateData = $request->only(['name', 'description', 'level', 'is_active']);

            if ($request->filled('name')) {
                $updateData['slug'] = Str::slug($request->name);
            }

            $role->update($updateData);

            // Mettre à jour les privilèges
            if ($request->has('privilege_ids')) {
                $role->privileges()->sync($request->privilege_ids ?? []);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rôle mis à jour avec succès',
                'data' => ['role' => $role->fresh()->load('privileges')->loadCount(['users', 'privileges'])]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour rôle', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer un rôle
     */
    public function deleteRole($roleId): JsonResponse
    {
        try {
            $role = Role::withCount('users')->find($roleId);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rôle introuvable'
                ], 404);
            }

            // Empêcher la suppression des rôles système
            if ($role->type === Role::TYPE_SYSTEM) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les rôles système ne peuvent pas être supprimés'
                ], 403);
            }

            // Vérifier s'il y a des utilisateurs avec ce rôle
            if ($role->users_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce rôle car il est assigné à ' . $role->users_count . ' utilisateur(s)'
                ], 400);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rôle supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression rôle', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression'
            ], 500);
        }
    }

    /**
     * Assigner des privilèges à un rôle
     */
    public function assignPrivilegesToRole(Request $request, $roleId): JsonResponse
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle introuvable'
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'privilege_ids' => 'required|array',
            'privilege_ids.*' => 'exists:privileges,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role->privileges()->sync($request->privilege_ids);

            return response()->json([
                'success' => true,
                'message' => 'Privilèges assignés avec succès',
                'data' => ['role' => $role->fresh()->load('privileges')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur assignation privilèges', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    // ===================================================================
    // GESTION DES UTILISATEURS PAR TYPE
    // ===================================================================

    /**
     * Liste des admins
     */
    public function admins(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'userType'])
                ->whereHas('roles', function ($q) {
                    $q->where('slug', Role::ADMIN);
                });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $admins = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $admins
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste admins', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des administrateurs'
            ], 500);
        }
    }

    /**
     * Liste des clients
     */
    public function clients(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'userType'])
                ->where('is_organizer', false)
                ->whereHas('roles', function ($q) {
                    $q->where('slug', Role::CLIENT);
                });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $clients = $query->withCount(['orders', 'tickets'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $clients
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste clients', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des clients'
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs organisateurs
     */
    public function organizersUsers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['roles', 'userType', 'organizers'])
                ->where('is_organizer', true);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $organizersUsers = $query->withCount(['organizers'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $organizersUsers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste utilisateurs organisateurs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des utilisateurs organisateurs'
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs supprimés (corbeille)
     */
    public function trashedUsers(Request $request): JsonResponse
    {
        try {
            $query = User::onlyTrashed()->with(['roles', 'userType']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $trashedUsers = $query->orderBy('deleted_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $trashedUsers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste utilisateurs supprimés', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des utilisateurs supprimés'
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur
     */
    public function showUser($userId): JsonResponse
    {
        try {
            $user = User::with(['roles.privileges', 'userType', 'organizers', 'orders', 'tickets'])
                ->withCount(['orders', 'tickets', 'organizers'])
                ->find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['user' => $user]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function deleteUser($userId): JsonResponse
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);
            }

            // Empêcher l'auto-suppression
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès (soft delete)'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur suppression utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la suppression'
            ], 500);
        }
    }

    /**
     * Restaurer un utilisateur supprimé
     */
    public function restoreUser($userId): JsonResponse
    {
        try {
            $user = User::onlyTrashed()->find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable dans la corbeille'
                ], 404);
            }

            $user->restore();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur restauré avec succès',
                'data' => ['user' => $user->fresh()->load('roles')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur restauration utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la restauration'
            ], 500);
        }
    }

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRoleToUser(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::find($request->role_id);

            if ($user->hasRole($role->slug)) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'utilisateur a déjà ce rôle'
                ], 400);
            }

            $user->roles()->attach($role->id, [
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rôle assigné avec succès',
                'data' => ['user' => $user->fresh()->load('roles')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur assignation rôle', [
                'user_id' => $userId,
                'role_id' => $request->role_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Retirer un rôle d'un utilisateur
     */
    public function removeRoleFromUser(Request $request, $userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable'
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role = Role::find($request->role_id);

            if (!$user->hasRole($role->slug)) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'utilisateur n\'a pas ce rôle'
                ], 400);
            }

            $user->roles()->detach($role->id);

            return response()->json([
                'success' => true,
                'message' => 'Rôle retiré avec succès',
                'data' => ['user' => $user->fresh()->load('roles')]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur retrait rôle', [
                'user_id' => $userId,
                'role_id' => $request->role_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un événement
     */
    public function toggleEventStatus($eventId): JsonResponse
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Événement introuvable'
                ], 404);
            }

            $event->is_active = !$event->is_active;
            $event->save();

            $message = $event->is_active ? 'Événement activé avec succès' : 'Événement désactivé avec succès';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['event' => $event->fresh()]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur toggle statut événement', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du changement de statut'
            ], 500);
        }
    }

    // ===================================================================
    // GESTION DES COMMANDES (ORDERS)
    // ===================================================================

    /**
     * Liste des commandes
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $query = Order::with(['event', 'buyer', 'organizer']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhereHas('buyer', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('organizer_id')) {
                $query->where('organizer_id', $request->organizer_id);
            }

            if ($request->filled('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            $orders = $query->withCount('tickets')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Stats pour le header
            $stats = [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'paid' => Order::where('status', 'paid')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste commandes admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des commandes'
            ], 500);
        }
    }

    /**
     * Afficher une commande
     */
    public function showOrder($orderId): JsonResponse
    {
        try {
            $order = Order::with(['event', 'buyer', 'organizer', 'tickets.ticketType', 'payment'])
                ->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['order' => $order]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage commande admin', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateOrderStatus(Request $request, $orderId): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }

            $order->status = $request->status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour',
                'data' => ['order' => $order->fresh()]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut commande', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Exporter les commandes
     */
    public function exportOrders(Request $request): JsonResponse
    {
        try {
            // Pour l'instant, retourner les données brutes
            // TODO: Implémenter l'export Excel/CSV
            $orders = Order::with(['event', 'buyer', 'organizer'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['orders' => $orders]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur export commandes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de l\'export'
            ], 500);
        }
    }

    // ===================================================================
    // GESTION DES PAIEMENTS (PAYMENTS)
    // ===================================================================

    /**
     * Liste des paiements
     */
    public function payments(Request $request): JsonResponse
    {
        try {
            $query = Payment::with(['order.event', 'order.buyer']);

            // Filtres
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('provider_txn_ref', 'like', "%{$search}%")
                      ->orWhereHas('order', function ($q2) use ($search) {
                          $q2->where('reference', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('provider')) {
                $query->where('provider', $request->provider);
            }

            if ($request->filled('organizer_id')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('organizer_id', $request->organizer_id);
                });
            }

            $payments = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Stats
            $stats = [
                'total' => Payment::count(),
                'success' => Payment::where('status', 'success')->count(),
                'initiated' => Payment::where('status', 'initiated')->count(),
                'failed' => Payment::where('status', 'failed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste paiements admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors du chargement des paiements'
            ], 500);
        }
    }

    /**
     * Afficher un paiement
     */
    public function showPayment($paymentId): JsonResponse
    {
        try {
            $payment = Payment::with(['order.event', 'order.buyer', 'order.organizer'])
                ->find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => ['payment' => $payment]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage paiement admin', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus($paymentId): JsonResponse
    {
        try {
            $payment = Payment::find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement introuvable'
                ], 404);
            }

            // TODO: Implémenter la vérification réelle auprès du provider
            return response()->json([
                'success' => true,
                'message' => 'Statut vérifié',
                'data' => ['payment' => $payment]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut paiement', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Vérifier tous les paiements en attente
     */
    public function checkPendingPayments(): JsonResponse
    {
        try {
            $pendingPayments = Payment::where('status', 'initiated')
                ->where('created_at', '>', now()->subDays(7))
                ->get();

            // TODO: Implémenter la vérification réelle auprès des providers

            return response()->json([
                'success' => true,
                'message' => count($pendingPayments) . ' paiements vérifiés',
                'data' => ['checked' => count($pendingPayments)]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur vérification paiements en attente', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Rembourser un paiement
     */
    public function refundPayment(Request $request, $paymentId): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = Payment::find($paymentId);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement introuvable'
                ], 404);
            }

            // TODO: Implémenter le remboursement réel auprès du provider
            $payment->status = 'refunded';
            $payment->save();

            $payment->order->status = 'refunded';
            $payment->order->save();

            return response()->json([
                'success' => true,
                'message' => 'Remboursement effectué',
                'data' => ['payment' => $payment]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur remboursement paiement', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Exporter les paiements
     */
    public function exportPayments(Request $request): JsonResponse
    {
        try {
            // Pour l'instant, retourner les données brutes
            // TODO: Implémenter l'export Excel/CSV
            $payments = Payment::with(['order.event', 'order.buyer'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ['payments' => $payments]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur export paiements', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de l\'export'
            ], 500);
        }
    }

    // ===================================================================
    // PROFIL ADMIN
    // ===================================================================

    /**
     * Profil de l'admin connecté
     */
    public function profile(): JsonResponse
    {
        try {
            $user = auth()->user()->load(['roles.privileges']);

            return response()->json([
                'success' => true,
                'data' => ['user' => $user]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur profil admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil admin
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'avatar_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update($request->only(['name', 'email', 'avatar_url']));

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour',
                'data' => ['user' => $user->fresh()]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour profil admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    // ===================================================================
    // NOTIFICATIONS
    // ===================================================================

    /**
     * Notifications de l'admin
     */
    public function notifications(): JsonResponse
    {
        try {
            // Pour l'instant, retourner un tableau vide
            // TODO: Implémenter le système de notifications
            $notifications = [];

            return response()->json([
                'success' => true,
                'data' => ['notifications' => $notifications]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notifications admin', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead($notificationId): JsonResponse
    {
        try {
            // TODO: Implémenter le système de notifications
            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur marquage notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique'
            ], 500);
        }
    }
}