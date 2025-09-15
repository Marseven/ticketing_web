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
}
