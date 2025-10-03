<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Checkin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Organizers",
 *     description="API Endpoints for organizer dashboard and management"
 * )
 */
class OrganizerController extends Controller
{
    /**
     * Get organizer dashboard with statistics and recent events
     * 
     * @OA\Get(
     *     path="/api/organizers/dashboard",
     *     operationId="getOrganizerDashboard",
     *     tags={"Organizers"},
     *     summary="Get organizer dashboard",
     *     description="Returns comprehensive dashboard data including statistics, scan data, and recent events for authenticated organizers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="stats",
     *                 type="object",
     *                 @OA\Property(property="total_events", type="integer", example=15),
     *                 @OA\Property(property="active_events", type="integer", example=8),
     *                 @OA\Property(property="total_tickets", type="integer", example=5420),
     *                 @OA\Property(property="used_tickets", type="integer", example=3821),
     *                 @OA\Property(property="usage_rate", type="number", format="float", example=70.5),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=125340.50)
     *             ),
     *             @OA\Property(
     *                 property="scan_stats",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date", example="2025-06-15"),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="valid", type="integer", example=142),
     *                     @OA\Property(property="duplicate", type="integer", example=6),
     *                     @OA\Property(property="invalid", type="integer", example=2),
     *                     @OA\Property(property="success_rate", type="number", format="float", example=94.7)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="recent_events",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Summer Music Festival"),
     *                     @OA\Property(property="status", type="string", enum={"draft", "published", "cancelled"}, example="published"),
     *                     @OA\Property(
     *                         property="next_schedule",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="starts_at", type="string", example="15/06/2025 20:00"),
     *                         @OA\Property(property="ends_at", type="string", example="15/06/2025 23:00")
     *                     ),
     *                     @OA\Property(property="tickets_sold", type="integer", example=850)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès refusé. Seuls les organisateurs peuvent accéder au dashboard.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder au dashboard.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        // Statistiques globales
        $totalEvents = Event::whereIn('organizer_id', $organizerIds)->count();
        $activeEvents = Event::whereIn('organizer_id', $organizerIds)->active()->count();
        
        $totalTickets = Ticket::whereHas('event', function ($query) use ($organizerIds) {
            $query->whereIn('organizer_id', $organizerIds);
        })->count();
        
        $usedTickets = Ticket::whereHas('event', function ($query) use ($organizerIds) {
            $query->whereIn('organizer_id', $organizerIds);
        })->where('status', 'used')->count();
        
        $totalRevenue = DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->whereIn('events.organizer_id', $organizerIds)
            ->whereIn('tickets.status', ['issued', 'used'])
            ->sum('ticket_types.price');

        // Statistiques de scan des 7 derniers jours
        $scanStats = Checkin::whereHas('ticket.event', function ($query) use ($organizerIds) {
            $query->whereIn('organizer_id', $organizerIds);
        })
        ->where('scanned_at', '>=', now()->subDays(7))
        ->select(
            DB::raw('DATE(scanned_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw("COUNT(CASE WHEN result = 'valid' THEN 1 END) as valid"),
            DB::raw("COUNT(CASE WHEN result = 'duplicate' THEN 1 END) as duplicate"),
            DB::raw("COUNT(CASE WHEN result = 'invalid' THEN 1 END) as invalid")
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();

        // Événements récents
        $recentEvents = Event::whereIn('organizer_id', $organizerIds)
            ->with(['schedules' => function ($query) {
                $query->orderBy('starts_at');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                $nextSchedule = $event->schedules->where('starts_at', '>', now())->first();
                
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'status' => $event->status,
                    'next_schedule' => $nextSchedule ? [
                        'starts_at' => $nextSchedule->starts_at->format('d/m/Y H:i'),
                        'ends_at' => $nextSchedule->ends_at->format('d/m/Y H:i'),
                    ] : null,
                    'tickets_sold' => $event->tickets->whereIn('status', ['issued', 'used'])->count(),
                ];
            });

        return response()->json([
            'stats' => [
                'total_events' => $totalEvents,
                'active_events' => $activeEvents,
                'total_tickets' => $totalTickets,
                'used_tickets' => $usedTickets,
                'usage_rate' => $totalTickets > 0 ? round(($usedTickets / $totalTickets) * 100, 1) : 0,
                'total_revenue' => round($totalRevenue, 2),
            ],
            'scan_stats' => $scanStats->map(function ($stat) {
                return [
                    'date' => $stat->date,
                    'total' => $stat->total,
                    'valid' => $stat->valid,
                    'duplicate' => $stat->duplicate,
                    'invalid' => $stat->invalid,
                    'success_rate' => $stat->total > 0 ? round(($stat->valid / $stat->total) * 100, 1) : 0,
                ];
            }),
            'recent_events' => $recentEvents,
        ]);
    }

    /**
     * Get organizer balances
     */
    public function balances(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder aux balances.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $balances = \App\Models\OrganizerBalance::whereIn('organizer_id', $organizerIds)
            ->with(['organizer:id,name'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['balances' => $balances]
        ]);
    }

    /**
     * Request a payout
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent demander des payouts.',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'gateway' => 'required|string|in:airtelmoney,moovmoney',
            'amount' => 'required|numeric|min:1000',
            'phone_number' => 'required|string|size:9|regex:/^[0-9]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $organizerIds = $user->organizers->pluck('id');

        // Vérifier le solde disponible
        $balance = \App\Models\OrganizerBalance::whereIn('organizer_id', $organizerIds)
            ->where('gateway', $request->gateway)
            ->first();

        if (!$balance || $balance->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Solde insuffisant pour ce payout'
            ], 400);
        }

        try {
            $payoutService = app(\App\Services\PayoutService::class);
            
            $result = $payoutService->createManualPayout(
                $balance->organizer_id,
                $request->gateway,
                $request->amount,
                $request->phone_number
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Demande de payout créée avec succès',
                    'data' => ['payout' => $result['payout']]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur demande payout organisateur', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la demande de payout'
            ], 500);
        }
    }

    /**
     * Get organizer events
     */
    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder aux événements.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $query = Event::whereIn('organizer_id', $organizerIds)
            ->with(['schedules', 'ticketTypes', 'venue', 'category'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ['events' => $events]
        ]);
    }

    /**
     * Get event sales details
     */
    public function eventSales(Request $request, $eventId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder aux ventes.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $event = Event::whereIn('organizer_id', $organizerIds)
            ->with(['tickets.ticketType', 'tickets.order'])
            ->findOrFail($eventId);

        // Statistiques des ventes
        $totalTickets = $event->tickets->count();
        $soldTickets = $event->tickets->whereIn('status', ['issued', 'used'])->count();
        $usedTickets = $event->tickets->where('status', 'used')->count();
        $revenue = $event->tickets->whereIn('status', ['issued', 'used'])
            ->sum(function($ticket) {
                return $ticket->ticketType->price;
            });

        // Ventes par type de ticket
        $salesByType = $event->ticketTypes->map(function($type) {
            $tickets = $type->tickets;
            return [
                'type_name' => $type->name,
                'price' => $type->price,
                'total_capacity' => $type->capacity,
                'sold' => $tickets->whereIn('status', ['issued', 'used'])->count(),
                'used' => $tickets->where('status', 'used')->count(),
                'revenue' => $tickets->whereIn('status', ['issued', 'used'])->count() * $type->price,
            ];
        });

        // Ventes par jour
        $salesByDay = $event->tickets->whereIn('status', ['issued', 'used'])
            ->groupBy(function($ticket) {
                return $ticket->created_at->format('Y-m-d');
            })
            ->map(function($tickets, $date) {
                return [
                    'date' => $date,
                    'count' => $tickets->count(),
                    'revenue' => $tickets->sum(function($ticket) {
                        return $ticket->ticketType->price;
                    }),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'event' => $event,
                'stats' => [
                    'total_tickets' => $totalTickets,
                    'sold_tickets' => $soldTickets,
                    'used_tickets' => $usedTickets,
                    'revenue' => $revenue,
                    'usage_rate' => $soldTickets > 0 ? round(($usedTickets / $soldTickets) * 100, 1) : 0,
                ],
                'sales_by_type' => $salesByType,
                'sales_by_day' => $salesByDay,
            ]
        ]);
    }

    /**
     * Get organizer payments
     */
    public function payments(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder aux paiements.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $query = \App\Models\Payment::whereHas('order', function($q) use ($organizerIds) {
            $q->whereIn('organizer_id', $organizerIds);
        })
        ->with(['order.event', 'order.organizer'])
        ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        $payments = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ['payments' => $payments]
        ]);
    }

    /**
     * Get organizer payouts
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder aux payouts.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $query = \App\Models\Payout::whereIn('organizer_id', $organizerIds)
            ->with(['organizer'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        $payouts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ['payouts' => $payouts]
        ]);
    }

    /**
     * Get dashboard stats for organizer
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent accéder au dashboard.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        $totalEvents = Event::whereIn('organizer_id', $organizerIds)->count();
        $activeEvents = Event::whereIn('organizer_id', $organizerIds)->active()->count();
        
        $ticketsSold = Ticket::whereHas('event', function ($query) use ($organizerIds) {
            $query->whereIn('organizer_id', $organizerIds);
        })->whereIn('status', ['issued', 'used'])->count();
        
        $totalRevenue = DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->whereIn('events.organizer_id', $organizerIds)
            ->whereIn('tickets.status', ['issued', 'used'])
            ->sum('ticket_types.price');

        return response()->json([
            'data' => [
                'total_events' => $totalEvents,
                'active_events' => $activeEvents,
                'tickets_sold' => $ticketsSold,
                'total_revenue' => round($totalRevenue, 2)
            ]
        ]);
    }

    /**
     * Get recent events for organizer
     */
    public function recentEvents(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');

        $recentEvents = Event::whereIn('organizer_id', $organizerIds)
            ->with(['venue:id,name', 'ticketTypes'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'venue_name' => $event->venue->name ?? '',
                    'event_date' => $event->event_date,
                    'status' => $event->is_active ? 'active' : 'draft',
                    'tickets_sold' => $event->tickets()->whereIn('status', ['issued', 'used'])->count()
                ];
            });

        return response()->json([
            'data' => $recentEvents
        ]);
    }

    /**
     * Get notifications for organizer
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        // Pour l'instant, on retourne des notifications simulées
        // TODO: Implémenter un système de notifications réel
        $notifications = collect([
            [
                'id' => 1,
                'type' => 'success',
                'title' => 'Nouvelle vente',
                'message' => 'Un nouveau billet a été vendu',
                'created_at' => now()->subHours(2)->toIso8601String()
            ]
        ]);

        return response()->json([
            'data' => $notifications
        ]);
    }

    /**
     * Get organizer profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $organizer = $user->organizers->first();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url
                ],
                'organizer' => $organizer
            ]
        ]);
    }

    /**
     * Update organizer profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'organizer_name' => 'sometimes|string|max:255',
            'organizer_description' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mise à jour des infos utilisateur
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone')) $user->phone = $request->phone;
        $user->save();

        // Mise à jour des infos organisateur
        if ($request->has('organizer_name') || $request->has('organizer_description')) {
            $organizer = $user->organizers->first();
            if ($organizer) {
                if ($request->has('organizer_name')) $organizer->name = $request->organizer_name;
                if ($request->has('organizer_description')) $organizer->bio = $request->organizer_description;
                $organizer->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'user' => $user,
                'organizer' => $user->organizers->first()
            ]
        ]);
    }

    /**
     * Upload organizer avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar_url = '/storage/' . $path;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploadé avec succès',
                'data' => [
                    'avatar_url' => $user->avatar_url
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload'
            ], 500);
        }
    }

    /**
     * Get balance details
     */
    public function getBalance(Request $request): JsonResponse
    {
        return $this->balances($request);
    }

    /**
     * Create a new event
     */
    public function createEvent(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }
        
        // Parser les données JSON si elles existent (pour FormData)
        if ($request->has('schedules') && is_string($request->schedules)) {
            $request->merge(['schedules' => json_decode($request->schedules, true)]);
        }
        if ($request->has('ticket_types') && is_string($request->ticket_types)) {
            $request->merge(['ticket_types' => json_decode($request->ticket_types, true)]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:event_categories,id',
            'venue_id' => 'nullable|exists:venues,id',
            'new_venue_name' => 'required_if:venue_id,new|nullable|string|max:255',
            'new_venue_city' => 'required_if:venue_id,new|nullable|string|max:255',
            'new_venue_address' => 'required_if:venue_id,new|nullable|string|max:500',
            'max_attendees' => 'nullable|integer|min:1',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'is_active' => 'boolean',
            'schedules' => 'required|array|min:1',
            'schedules.*.starts_at' => 'required|date',
            'schedules.*.ends_at' => 'required|date|after:schedules.*.starts_at',
            'schedules.*.door_time' => 'nullable|date',
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.description' => 'nullable|string',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.capacity' => 'required|integer|min:1',
            'ticket_types.*.is_active' => 'boolean'
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

            $organizer = $user->organizers->first();
            if (!$organizer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun organisateur associé à cet utilisateur'
                ], 400);
            }

            // Créer le lieu si nécessaire
            $venueId = $request->venue_id;
            if ($venueId === 'new' && $request->new_venue_name) {
                $venue = \App\Models\Venue::create([
                    'name' => $request->new_venue_name,
                    'city' => $request->new_venue_city,
                    'address' => $request->new_venue_address,
                    'capacity' => $request->max_attendees,
                    'is_active' => true
                ]);
                $venueId = $venue->id;
            } elseif ($venueId === 'new') {
                $venueId = null; // Si pas de données pour nouveau lieu
            }

            // Debug: Logging des données reçues
            \Illuminate\Support\Facades\Log::info('CreateEvent - Données reçues:', [
                'has_image_file' => $request->hasFile('image_file'),
                'image_url' => $request->image_url,
                'schedules_type' => gettype($request->schedules),
                'ticket_types_type' => gettype($request->ticket_types),
                'all_keys' => array_keys($request->all())
            ]);
            
            // Gérer l'upload d'image si présent
            $imageUrl = $request->image_url;
            $imageFile = null;
            
            if ($request->hasFile('image_file')) {
                $file = $request->file('image_file');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('images/events', $filename, 'public');
                $imageFile = $filename;
                $imageUrl = null; // Si on a un fichier, on ignore l'URL
                
                \Illuminate\Support\Facades\Log::info('CreateEvent - Fichier uploadé:', [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize()
                ]);
            }
            
            // Créer l'événement
            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'slug' => \Illuminate\Support\Str::slug($request->title . '-' . time()),
                'event_date' => $request->event_date,
                'organizer_id' => $organizer->id,
                'category_id' => $request->category_id,
                'venue_id' => $venueId,
                'max_attendees' => $request->max_attendees,
                'image_url' => $imageUrl,
                'image_file' => $imageFile,
                'is_active' => $request->is_active ?? false,
                'status' => $request->is_active ? 'published' : 'draft'
            ]);

            // Créer les horaires
            foreach ($request->schedules as $scheduleData) {
                \App\Models\Schedule::create([
                    'event_id' => $event->id,
                    'starts_at' => $scheduleData['starts_at'],
                    'ends_at' => $scheduleData['ends_at'],
                    'door_time' => $scheduleData['door_time'] ?? null
                ]);
            }

            // Créer les types de billets
            foreach ($request->ticket_types as $ticketTypeData) {
                \App\Models\TicketType::create([
                    'event_id' => $event->id,
                    'name' => $ticketTypeData['name'],
                    'description' => $ticketTypeData['description'],
                    'price' => $ticketTypeData['price'],
                    'available_quantity' => $ticketTypeData['capacity'],
                    'status' => ($ticketTypeData['is_active'] ?? true) ? 'active' : 'inactive'
                ]);
            }

            DB::commit();

            // Recharger l'événement avec ses relations
            $event->load(['venue', 'category', 'schedules', 'ticketTypes']);

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès',
                'data' => $event
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error('Erreur création événement', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'événement'
            ], 500);
        }
    }

    /**
     * Update an event
     */
    public function updateEvent(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        $event = Event::whereIn('organizer_id', $organizerIds)
            ->findOrFail($id);
        
        // Parser les données JSON si elles existent (pour FormData)
        if ($request->has('schedules') && is_string($request->schedules)) {
            $request->merge(['schedules' => json_decode($request->schedules, true)]);
        }
        if ($request->has('ticket_types') && is_string($request->ticket_types)) {
            $request->merge(['ticket_types' => json_decode($request->ticket_types, true)]);
        }
        
        // Debug: Log file upload info
        \Illuminate\Support\Facades\Log::info('EventEdit Debug', [
            'event_id' => $id,
            'has_image_file' => $request->hasFile('image_file'),
            'image_file_info' => $request->hasFile('image_file') ? [
                'name' => $request->file('image_file')->getClientOriginalName(),
                'size' => $request->file('image_file')->getSize(),
                'mime' => $request->file('image_file')->getMimeType()
            ] : null,
            'image_url' => $request->image_url,
            'request_content_type' => $request->header('Content-Type'),
            'all_files' => array_keys($request->allFiles())
        ]);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|exists:event_categories,id',
            'venue_id' => 'sometimes|nullable|exists:venues,id',
            'new_venue_name' => 'required_if:venue_id,new|nullable|string|max:255',
            'new_venue_city' => 'required_if:venue_id,new|nullable|string|max:255',
            'new_venue_address' => 'required_if:venue_id,new|nullable|string|max:500',
            'image_url' => 'sometimes|nullable|string',
            'image_file' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,published,cancelled',
            'schedules' => 'sometimes|array',
            'schedules.*.starts_at' => 'required_with:schedules|date',
            'schedules.*.ends_at' => 'required_with:schedules|date|after:schedules.*.starts_at',
            'schedules.*.door_time' => 'nullable|date',
            'ticket_types' => 'sometimes|array',
            'ticket_types.*.id' => 'nullable|integer',
            'ticket_types.*.name' => 'required_with:ticket_types|string|max:255',
            'ticket_types.*.description' => 'nullable|string',
            'ticket_types.*.price' => 'required_with:ticket_types|numeric|min:0',
            'ticket_types.*.capacity' => 'required_with:ticket_types|integer|min:1',
            'ticket_types.*.is_active' => 'boolean'
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

            // Créer le lieu si nécessaire
            if ($request->venue_id === 'new' && $request->new_venue_name) {
                $venue = \App\Models\Venue::create([
                    'name' => $request->new_venue_name,
                    'city' => $request->new_venue_city,
                    'address' => $request->new_venue_address,
                    'is_active' => true
                ]);
                $request->merge(['venue_id' => $venue->id]);
            }

            // Gérer l'upload d'image si présent
            $updateData = $request->only([
                'title', 'description', 'category_id', 'venue_id', 'is_active', 'status'
            ]);
            
            if ($request->hasFile('image_file')) {
                // Supprimer l'ancienne image si elle existe
                if ($event->image_file) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete('images/events/' . $event->image_file);
                }
                
                // Stocker la nouvelle image
                $file = $request->file('image_file');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('images/events', $filename, 'public');
                
                $updateData['image_file'] = $filename;
                $updateData['image_url'] = null; // Clear URL si on a un fichier
                
                \Illuminate\Support\Facades\Log::info('File uploaded successfully', [
                    'filename' => $filename,
                    'path' => $path,
                    'event_id' => $event->id
                ]);
            } elseif ($request->has('image_url')) {
                // Si une URL est fournie
                $updateData['image_url'] = $request->image_url;
                // Optionnellement, supprimer l'ancien fichier si on passe à une URL
                if ($event->image_file) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete('images/events/' . $event->image_file);
                    $updateData['image_file'] = null;
                }
                
                \Illuminate\Support\Facades\Log::info('Image URL updated', [
                    'image_url' => $request->image_url,
                    'event_id' => $event->id
                ]);
            }
            
            \Illuminate\Support\Facades\Log::info('UpdateData before event update', [
                'updateData' => $updateData,
                'event_id' => $event->id
            ]);
            
            // Mettre à jour l'événement principal
            $event->update($updateData);

            // Mettre à jour les horaires
            if ($request->has('schedules')) {
                // Supprimer les anciens horaires
                $event->schedules()->delete();
                
                // Créer les nouveaux horaires
                foreach ($request->schedules as $scheduleData) {
                    \App\Models\Schedule::create([
                        'event_id' => $event->id,
                        'starts_at' => $scheduleData['starts_at'],
                        'ends_at' => $scheduleData['ends_at'],
                        'door_time' => $scheduleData['door_time'] ?? null
                    ]);
                }
            }

            // Mettre à jour les types de billets
            if ($request->has('ticket_types')) {
                $submittedIds = collect($request->ticket_types)->pluck('id')->filter()->toArray();
                
                // Supprimer les types de billets qui ne sont plus dans la liste
                $event->ticketTypes()->whereNotIn('id', $submittedIds)->delete();
                
                // Créer ou mettre à jour les types de billets
                foreach ($request->ticket_types as $ticketTypeData) {
                    if (isset($ticketTypeData['id']) && $ticketTypeData['id']) {
                        // Mettre à jour le type existant
                        \App\Models\TicketType::where('id', $ticketTypeData['id'])
                            ->where('event_id', $event->id)
                            ->update([
                                'name' => $ticketTypeData['name'],
                                'description' => $ticketTypeData['description'],
                                'price' => $ticketTypeData['price'],
                                'available_quantity' => $ticketTypeData['capacity'],
                                'status' => ($ticketTypeData['is_active'] ?? true) ? 'active' : 'inactive'
                            ]);
                    } else {
                        // Créer un nouveau type
                        \App\Models\TicketType::create([
                            'event_id' => $event->id,
                            'name' => $ticketTypeData['name'],
                            'description' => $ticketTypeData['description'],
                            'price' => $ticketTypeData['price'],
                            'available_quantity' => $ticketTypeData['capacity'],
                            'status' => ($ticketTypeData['is_active'] ?? true) ? 'active' : 'inactive'
                        ]);
                    }
                }
            }

            DB::commit();

            // Recharger l'événement avec ses relations
            $event->load(['venue', 'category', 'schedules', 'ticketTypes']);

            return response()->json([
                'success' => true,
                'message' => 'Événement mis à jour avec succès',
                'data' => $event
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error('Erreur mise à jour événement', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Get a specific event
     */
    public function getEvent(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        $event = Event::whereIn('organizer_id', $organizerIds)
            ->with(['venue', 'category', 'schedules', 'ticketTypes', 'tickets'])
            ->findOrFail($id);

        // S'assurer que les ticket_types ont leurs attributs calculés
        $event->ticketTypes->each(function ($ticketType) {
            // Force le calcul des attributs calculés
            $ticketType->capacity;
            $ticketType->sold_quantity;
            $ticketType->remaining_quantity;
        });

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }
}
