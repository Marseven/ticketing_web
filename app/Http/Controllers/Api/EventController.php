<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Events",
 *     description="API Endpoints for managing events"
 * )
 */
class EventController extends Controller
{
    /**
     * Liste des événements accessibles à l'utilisateur connecté
     * 
     * @OA\Get(
     *     path="/api/events",
     *     operationId="getEventsList",
     *     tags={"Events"},
     *     summary="Get list of events for the authenticated organizer",
     *     description="Returns a list of all events accessible to the authenticated organizer user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="events",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Concert Summer Festival"),
     *                     @OA\Property(property="slug", type="string", example="concert-summer-festival"),
     *                     @OA\Property(property="description", type="string", example="Amazing summer festival with great artists"),
     *                     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                     @OA\Property(property="status", type="string", enum={"draft", "published", "cancelled"}, example="published"),
     *                     @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="12/06/2025 14:30:00"),
     *                     @OA\Property(
     *                         property="venue",
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Madison Square Garden"),
     *                         @OA\Property(property="city", type="string", example="New York"),
     *                         @OA\Property(property="address", type="string", example="4 Pennsylvania Plaza")
     *                     ),
     *                     @OA\Property(
     *                         property="organizer",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Live Nation"),
     *                         @OA\Property(property="slug", type="string", example="live-nation")
     *                     ),
     *                     @OA\Property(
     *                         property="schedules",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="starts_at", type="string", format="date-time", example="15/06/2025 20:00:00"),
     *                             @OA\Property(property="ends_at", type="string", format="date-time", example="15/06/2025 23:00:00"),
     *                             @OA\Property(property="door_time", type="string", format="date-time", nullable=true, example="15/06/2025 19:00:00"),
     *                             @OA\Property(property="status", type="string", enum={"active", "cancelled", "postponed"}, example="active"),
     *                             @OA\Property(
     *                                 property="capacity",
     *                                 type="object",
     *                                 @OA\Property(property="max", type="integer", example=20000),
     *                                 @OA\Property(property="current", type="integer", example=15000),
     *                                 @OA\Property(property="available", type="integer", example=5000)
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="ticket_types",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="General Admission"),
     *                             @OA\Property(property="description", type="string", nullable=true, example="Standard entry ticket"),
     *                             @OA\Property(property="price", type="number", format="float", example=75.00),
     *                             @OA\Property(property="currency", type="string", example="USD"),
     *                             @OA\Property(property="available_quantity", type="integer", nullable=true, example=10000),
     *                             @OA\Property(property="sold_quantity", type="integer", example=8500)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès refusé. Seuls les organisateurs peuvent voir les événements.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent voir les événements.',
            ], 403);
        }

        // Récupérer les événements des organisateurs de l'utilisateur
        $organizerIds = $user->organizers->pluck('id');
        
        $events = Event::whereIn('organizer_id', $organizerIds)
                      ->with(['organizer', 'schedules', 'ticketTypes'])
                      ->orderBy('published_at', 'desc')
                      ->get()
                      ->map(function ($event) {
                          return [
                              'id' => $event->id,
                              'title' => $event->title,
                              'slug' => $event->slug,
                              'description' => $event->description,
                              'image_url' => $event->image_url,
                              'status' => $event->status,
                              'published_at' => $event->published_at?->format('d/m/Y H:i:s'),
                              'venue' => [
                                  'name' => $event->venue_name,
                                  'city' => $event->venue_city,
                                  'address' => $event->venue_address,
                              ],
                              'organizer' => [
                                  'id' => $event->organizer->id,
                                  'name' => $event->organizer->name,
                                  'slug' => $event->organizer->slug,
                              ],
                              'schedules' => $event->schedules->map(function ($schedule) {
                                  return [
                                      'id' => $schedule->id,
                                      'starts_at' => $schedule->starts_at->format('d/m/Y H:i:s'),
                                      'ends_at' => $schedule->ends_at->format('d/m/Y H:i:s'),
                                      'door_time' => $schedule->door_time?->format('d/m/Y H:i:s'),
                                      'status' => $schedule->status,
                                      'capacity' => [
                                          'max' => $schedule->max_capacity,
                                          'current' => $schedule->current_capacity,
                                          'available' => $schedule->available_capacity,
                                      ],
                                  ];
                              }),
                              'ticket_types' => $event->ticketTypes->map(function ($ticketType) {
                                  return [
                                      'id' => $ticketType->id,
                                      'name' => $ticketType->name,
                                      'description' => $ticketType->description,
                                      'price' => $ticketType->price,
                                      'currency' => $ticketType->currency,
                                      'available_quantity' => $ticketType->available_quantity,
                                      'sold_quantity' => $ticketType->sold_quantity,
                                  ];
                              }),
                          ];
                      });

        return response()->json([
            'events' => $events,
        ]);
    }

    /**
     * Détails d'un événement spécifique
     * 
     * @OA\Get(
     *     path="/api/events/{id}",
     *     operationId="getEventById",
     *     tags={"Events"},
     *     summary="Get event details by ID",
     *     description="Returns detailed information about a specific event including venue, schedules, ticket types and statistics",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Event ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="event",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Concert Summer Festival"),
     *                 @OA\Property(property="slug", type="string", example="concert-summer-festival"),
     *                 @OA\Property(property="description", type="string", example="Amazing summer festival with great artists"),
     *                 @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                 @OA\Property(property="status", type="string", enum={"draft", "published", "cancelled"}, example="published"),
     *                 @OA\Property(property="published_at", type="string", format="date-time", nullable=true, example="12/06/2025 14:30:00"),
     *                 @OA\Property(
     *                     property="venue",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Madison Square Garden"),
     *                     @OA\Property(property="city", type="string", example="New York"),
     *                     @OA\Property(property="address", type="string", example="4 Pennsylvania Plaza"),
     *                     @OA\Property(property="latitude", type="number", format="float", nullable=true, example=40.7505),
     *                     @OA\Property(property="longitude", type="number", format="float", nullable=true, example=-73.9934)
     *                 ),
     *                 @OA\Property(
     *                     property="organizer",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Live Nation"),
     *                     @OA\Property(property="slug", type="string", example="live-nation")
     *                 ),
     *                 @OA\Property(
     *                     property="schedules",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="starts_at", type="string", format="date-time", example="15/06/2025 20:00:00"),
     *                         @OA\Property(property="ends_at", type="string", format="date-time", example="15/06/2025 23:00:00"),
     *                         @OA\Property(property="door_time", type="string", format="date-time", nullable=true, example="15/06/2025 19:00:00"),
     *                         @OA\Property(property="status", type="string", enum={"active", "cancelled", "postponed"}, example="active"),
     *                         @OA\Property(
     *                             property="capacity",
     *                             type="object",
     *                             @OA\Property(property="max", type="integer", example=20000),
     *                             @OA\Property(property="current", type="integer", example=15000),
     *                             @OA\Property(property="available", type="integer", example=5000)
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="ticket_types",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="General Admission"),
     *                         @OA\Property(property="description", type="string", nullable=true, example="Standard entry ticket"),
     *                         @OA\Property(property="price", type="number", format="float", example=75.00),
     *                         @OA\Property(property="currency", type="string", example="USD"),
     *                         @OA\Property(property="available_quantity", type="integer", nullable=true, example=10000),
     *                         @OA\Property(property="sold_quantity", type="integer", example=8500),
     *                         @OA\Property(property="remaining_quantity", type="integer", example=1500)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="stats",
     *                     type="object",
     *                     @OA\Property(property="total_tickets", type="integer", example=15000),
     *                     @OA\Property(property="sold_tickets", type="integer", example=12000),
     *                     @OA\Property(property="used_tickets", type="integer", example=8500),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=900000.00)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès refusé. Seuls les organisateurs peuvent voir les événements.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Événement non trouvé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent voir les événements.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        $event = Event::whereIn('organizer_id', $organizerIds)
                     ->with(['organizer', 'schedules', 'ticketTypes', 'tickets'])
                     ->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Événement non trouvé.',
            ], 404);
        }

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'description' => $event->description,
                'image_url' => $event->image_url,
                'status' => $event->status,
                'published_at' => $event->published_at?->format('d/m/Y H:i:s'),
                'venue' => [
                    'name' => $event->venue_name,
                    'city' => $event->venue_city,
                    'address' => $event->venue_address,
                    'latitude' => $event->venue_latitude,
                    'longitude' => $event->venue_longitude,
                ],
                'organizer' => [
                    'id' => $event->organizer->id,
                    'name' => $event->organizer->name,
                    'slug' => $event->organizer->slug,
                ],
                'schedules' => $event->schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'starts_at' => $schedule->starts_at->format('d/m/Y H:i:s'),
                        'ends_at' => $schedule->ends_at->format('d/m/Y H:i:s'),
                        'door_time' => $schedule->door_time?->format('d/m/Y H:i:s'),
                        'status' => $schedule->status,
                        'capacity' => [
                            'max' => $schedule->max_capacity,
                            'current' => $schedule->current_capacity,
                            'available' => $schedule->available_capacity,
                        ],
                    ];
                }),
                'ticket_types' => $event->ticketTypes->map(function ($ticketType) {
                    return [
                        'id' => $ticketType->id,
                        'name' => $ticketType->name,
                        'description' => $ticketType->description,
                        'price' => $ticketType->price,
                        'currency' => $ticketType->currency,
                        'available_quantity' => $ticketType->available_quantity,
                        'sold_quantity' => $ticketType->sold_quantity,
                        'remaining_quantity' => $ticketType->remaining_quantity,
                    ];
                }),
                'stats' => [
                    'total_tickets' => $event->tickets->count(),
                    'sold_tickets' => $event->tickets->whereIn('status', ['issued', 'used'])->count(),
                    'used_tickets' => $event->tickets->where('status', 'used')->count(),
                    'total_revenue' => $event->tickets->whereIn('status', ['issued', 'used'])->sum(function ($ticket) {
                        return $ticket->ticketType->price ?? 0;
                    }),
                ],
            ],
        ]);
    }

    /**
     * Statistiques de scan pour un événement
     * 
     * @OA\Get(
     *     path="/api/events/{id}/scan-stats",
     *     operationId="getEventScanStats",
     *     tags={"Events"},
     *     summary="Get scan statistics for an event",
     *     description="Returns detailed scan statistics including ticket counts, scan results and recent scan history",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Event ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="event",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Concert Summer Festival")
     *             ),
     *             @OA\Property(
     *                 property="tickets",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=15000, description="Total number of tickets"),
     *                 @OA\Property(property="issued", type="integer", example=12000, description="Number of issued tickets"),
     *                 @OA\Property(property="used", type="integer", example=8500, description="Number of used tickets"),
     *                 @OA\Property(property="refunded", type="integer", example=200, description="Number of refunded tickets"),
     *                 @OA\Property(property="void", type="integer", example=50, description="Number of void tickets")
     *             ),
     *             @OA\Property(
     *                 property="scans",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=9000, description="Total number of scans"),
     *                 @OA\Property(property="valid", type="integer", example=8500, description="Number of valid scans"),
     *                 @OA\Property(property="duplicate", type="integer", example=450, description="Number of duplicate scans"),
     *                 @OA\Property(property="invalid", type="integer", example=50, description="Number of invalid scans")
     *             ),
     *             @OA\Property(
     *                 property="recent_scans",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="ticket_code", type="string", example="TKT-2025-ABC123"),
     *                     @OA\Property(property="result", type="string", enum={"valid", "duplicate", "invalid"}, example="valid"),
     *                     @OA\Property(property="scanned_at", type="string", format="date-time", example="15/06/2025 20:15:30"),
     *                     @OA\Property(property="scanner", type="string", example="John Doe"),
     *                     @OA\Property(property="device_id", type="string", nullable=true, example="DEVICE-001")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Accès refusé. Seuls les organisateurs peuvent voir les statistiques.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Événement non trouvé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function scanStats(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Accès refusé. Seuls les organisateurs peuvent voir les statistiques.',
            ], 403);
        }

        $organizerIds = $user->organizers->pluck('id');
        
        $event = Event::whereIn('organizer_id', $organizerIds)
                     ->with(['tickets.checkins'])
                     ->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Événement non trouvé.',
            ], 404);
        }

        $tickets = $event->tickets;
        $checkins = $tickets->flatMap->checkins;

        $stats = [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
            ],
            'tickets' => [
                'total' => $tickets->count(),
                'issued' => $tickets->where('status', 'issued')->count(),
                'used' => $tickets->where('status', 'used')->count(),
                'refunded' => $tickets->where('status', 'refunded')->count(),
                'void' => $tickets->where('status', 'void')->count(),
            ],
            'scans' => [
                'total' => $checkins->count(),
                'valid' => $checkins->where('result', 'valid')->count(),
                'duplicate' => $checkins->where('result', 'duplicate')->count(),
                'invalid' => $checkins->where('result', 'invalid')->count(),
            ],
            'recent_scans' => $checkins->sortByDesc('scanned_at')->take(10)->map(function ($checkin) {
                return [
                    'ticket_code' => $checkin->ticket->code,
                    'result' => $checkin->result,
                    'scanned_at' => $checkin->scanned_at->format('d/m/Y H:i:s'),
                    'scanner' => $checkin->scanner->name ?? 'Système',
                    'device_id' => $checkin->device_id,
                ];
            })->values(),
        ];

        return response()->json($stats);
    }
}