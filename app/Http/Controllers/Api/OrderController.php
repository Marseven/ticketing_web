<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="API Endpoints for managing orders"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     operationId="createOrder",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     description="Creates a new ticket order for an event",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_id","schedule_id","tickets"},
     *             @OA\Property(property="event_id", type="integer", example=1, description="ID of the event"),
     *             @OA\Property(property="schedule_id", type="integer", example=1, description="ID of the event schedule"),
     *             @OA\Property(
     *                 property="tickets",
     *                 type="array",
     *                 description="Array of ticket types and quantities to order",
     *                 @OA\Items(
     *                     required={"ticket_type_id","quantity"},
     *                     @OA\Property(property="ticket_type_id", type="integer", example=1, description="ID of the ticket type"),
     *                     @OA\Property(property="quantity", type="integer", minimum=1, example=2, description="Number of tickets")
     *                 )
     *             ),
     *             @OA\Property(property="promo_code", type="string", nullable=true, example="SUMMER20", description="Optional promotional code"),
     *             @OA\Property(
     *                 property="attendee_info",
     *                 type="object",
     *                 nullable=true,
     *                 description="Additional attendee information",
     *                 @OA\Property(property="first_name", type="string", example="Jane"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-2025-000001"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "paid", "cancelled", "refunded"}, example="pending"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=150.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="12/09/2025 14:30:00"),
     *                 @OA\Property(property="payment_deadline", type="string", format="date-time", example="12/09/2025 14:45:00"),
     *                 @OA\Property(
     *                     property="event",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Concert Summer Festival"),
     *                     @OA\Property(property="venue_name", type="string", example="Madison Square Garden")
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="starts_at", type="string", format="date-time", example="15/06/2025 20:00:00"),
     *                     @OA\Property(property="ends_at", type="string", format="date-time", example="15/06/2025 23:00:00")
     *                 ),
     *                 @OA\Property(
     *                     property="order_items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="ticket_type", type="string", example="General Admission"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=75.00),
     *                         @OA\Property(property="total_price", type="number", format="float", example=150.00)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Commande créée avec succès"),
     *             @OA\Property(property="payment_url", type="string", nullable=true, example="https://payment.example.com/checkout/abc123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quantité de billets insuffisante")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="event_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="L'événement sélectionné n'existe pas.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OrderController store method - À implémenter")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Validation des données
        $validated = $request->validate([
            'event_slug' => 'required|string|exists:events,slug',
            'ticket_type_id' => 'required|integer|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        try {
            \DB::beginTransaction();

            $user = $request->user();

            // Récupérer l'événement
            $event = \App\Models\Event::where('slug', $validated['event_slug'])
                ->with(['ticketTypes', 'schedules'])
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Événement non trouvé'
                ], 404);
            }

            // Vérifier que l'événement est publié et actif
            if ($event->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet événement n\'est pas disponible pour la réservation'
                ], 400);
            }

            // Récupérer le type de billet
            $ticketType = $event->ticketTypes()
                ->where('id', $validated['ticket_type_id'])
                ->where('status', 'active')
                ->first();

            if (!$ticketType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de billet non trouvé ou non disponible'
                ], 404);
            }

            // Vérifier la disponibilité des billets
            $soldQuantity = \DB::table('tickets')
                ->where('ticket_type_id', $ticketType->id)
                ->whereIn('status', ['issued', 'used'])
                ->count();

            // Si available_quantity est null, c'est illimité
            if ($ticketType->available_quantity !== null) {
                $availableQuantity = $ticketType->available_quantity - $soldQuantity;

                if ($availableQuantity < $validated['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Seulement {$availableQuantity} billets disponibles pour ce type"
                    ], 400);
                }
            }

            // Vérifier si l'événement est passé
            if ($event->schedules->isNotEmpty()) {
                $nextSchedule = $event->schedules->where('status', 'active')->first();
                if ($nextSchedule && $nextSchedule->starts_at < now()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de réserver des billets pour un événement passé'
                    ], 400);
                }
            }

            // Calculer les montants
            $unitPrice = $ticketType->price ?? 0;
            $subtotalAmount = $unitPrice * $validated['quantity'];
            $feesAmount = $this->calculateFees($subtotalAmount);
            $taxAmount = $this->calculateTaxes($subtotalAmount);
            $totalAmount = $subtotalAmount + $feesAmount + $taxAmount;

            // Créer la commande pour l'utilisateur authentifié
            $order = \App\Models\Order::create([
                'organizer_id' => $event->organizer_id,
                'buyer_id' => $user->id,
                'currency' => 'XAF',
                'subtotal_amount' => $subtotalAmount,
                'fees_amount' => $feesAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'reference' => $this->generateOrderReference(),
                'placed_at' => now(),
                'is_guest_order' => false,
            ]);

            // Créer les billets
            for ($i = 0; $i < $validated['quantity']; $i++) {
                \App\Models\Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketType->id,
                    'schedule_id' => $event->schedules->where('status', 'active')->first()?->id,
                    'buyer_id' => $user->id,
                    'code' => $this->generateTicketCode(),
                    'status' => 'issued',
                    'issued_at' => now(),
                ]);
            }

            \DB::commit();

            // Préparer la réponse
            $orderData = [
                'id' => $order->id,
                'reference' => $order->reference,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $order->status,
                'tickets_count' => $validated['quantity'],
                'event' => [
                    'title' => $event->title,
                    'slug' => $event->slug,
                ],
                'placed_at' => $order->placed_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'order' => $orderData,
                    'payment_url' => "/payment/{$order->reference}",
                ]
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * Calculate fees for the order
     */
    private function calculateFees(float $subtotal): float
    {
        // Frais de service de 5%
        return $subtotal * 0.05;
    }

    /**
     * Calculate taxes for the order
     */
    private function calculateTaxes(float $subtotal): float
    {
        // TVA de 18% au Gabon
        return $subtotal * 0.18;
    }

    /**
     * Generate unique order reference
     */
    private function generateOrderReference(): string
    {
        do {
            $reference = 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (\App\Models\Order::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate unique ticket code
     */
    private function generateTicketCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (\App\Models\Ticket::where('code', $code)->exists());

        return $code;
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     operationId="getUserOrders",
     *     tags={"Orders"},
     *     summary="Get list of user orders",
     *     description="Returns a paginated list of orders for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "paid", "cancelled", "refunded"})
     *     ),
     *     @OA\Parameter(
     *         name="event_id",
     *         in="query",
     *         description="Filter by event ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter orders from this date (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter orders to this date (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="orders",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="order_number", type="string", example="ORD-2025-000001"),
     *                     @OA\Property(property="status", type="string", enum={"pending", "paid", "cancelled", "refunded"}, example="paid"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=150.00),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="12/09/2025 14:30:00"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example="12/09/2025 14:35:00"),
     *                     @OA\Property(
     *                         property="event",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Concert Summer Festival"),
     *                         @OA\Property(property="venue_name", type="string", example="Madison Square Garden"),
     *                         @OA\Property(property="venue_city", type="string", example="New York")
     *                     ),
     *                     @OA\Property(
     *                         property="schedule",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="starts_at", type="string", format="date-time", example="15/06/2025 20:00:00")
     *                     ),
     *                     @OA\Property(property="tickets_count", type="integer", example=2, description="Total number of tickets in this order")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OrderController index method - À implémenter")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = $user->orders()
            ->with(['tickets.event', 'tickets.ticketType', 'tickets.schedule']);
        
        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrage par event_id
        if ($request->filled('event_id')) {
            $query->whereHas('tickets.event', function($q) use ($request) {
                $q->where('id', $request->event_id);
            });
        }
        
        // Filtrage par date
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }
        
        $perPage = min($request->get('per_page', 20), 100);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Formater les données pour correspondre à la structure attendue
        $formattedOrders = $orders->map(function($order) {
            $firstTicket = $order->tickets->first();
            $event = $firstTicket?->event;
            $schedule = $firstTicket?->schedule;

            return [
                'id' => $order->id,
                'order_number' => $order->reference,
                'status' => $order->status === 'completed' ? 'paid' : $order->status,
                'total_amount' => $order->total_amount,
                'subtotal_amount' => $order->subtotal_amount,
                'currency' => $order->currency ?? 'XAF',
                'created_at' => $order->created_at->format('d/m/Y H:i:s'),
                'paid_at' => $order->status === 'completed' ? $order->updated_at->format('d/m/Y H:i:s') : null,
                'event' => $event ? [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'image' => $event->getImageUrl('medium'),
                    'venue_name' => $event->venue_name,
                    'venue_city' => $event->venue_city
                ] : null,
                'schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'starts_at' => $schedule->starts_at->format('d/m/Y H:i:s')
                ] : null,
                'tickets' => $order->tickets->map(function($ticket) {
                    return [
                        'id' => $ticket->id,
                        'code' => $ticket->code,
                        'status' => $ticket->status,
                        'ticket_type' => $ticket->ticketType ? [
                            'id' => $ticket->ticketType->id,
                            'name' => $ticket->ticketType->name,
                            'price' => $ticket->ticketType->price
                        ] : null
                    ];
                }),
                'tickets_count' => $order->tickets->count()
            ];
        });
        
        return response()->json([
            'orders' => $formattedOrders,
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     operationId="getOrderById",
     *     tags={"Orders"},
     *     summary="Get specific order details",
     *     description="Returns detailed information about a specific order including tickets and payment details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-2025-000001"),
     *                 @OA\Property(property="status", type="string", enum={"pending", "paid", "cancelled", "refunded"}, example="paid"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=150.00),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="12/09/2025 14:30:00"),
     *                 @OA\Property(property="paid_at", type="string", format="date-time", nullable=true, example="12/09/2025 14:35:00"),
     *                 @OA\Property(property="payment_deadline", type="string", format="date-time", nullable=true, example="12/09/2025 14:45:00"),
     *                 @OA\Property(property="cancelled_at", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="cancellation_reason", type="string", nullable=true, example=null),
     *                 @OA\Property(
     *                     property="event",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Concert Summer Festival"),
     *                     @OA\Property(property="description", type="string", example="Amazing summer festival with great artists"),
     *                     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/event.jpg"),
     *                     @OA\Property(property="venue_name", type="string", example="Madison Square Garden"),
     *                     @OA\Property(property="venue_city", type="string", example="New York"),
     *                     @OA\Property(property="venue_address", type="string", example="4 Pennsylvania Plaza")
     *                 ),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="starts_at", type="string", format="date-time", example="15/06/2025 20:00:00"),
     *                     @OA\Property(property="ends_at", type="string", format="date-time", example="15/06/2025 23:00:00"),
     *                     @OA\Property(property="door_time", type="string", format="date-time", nullable=true, example="15/06/2025 19:00:00"),
     *                     @OA\Property(property="status", type="string", enum={"active", "cancelled", "postponed"}, example="active")
     *                 ),
     *                 @OA\Property(
     *                     property="order_items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="ticket_type", type="string", example="General Admission"),
     *                         @OA\Property(property="ticket_description", type="string", nullable=true, example="Standard entry ticket"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=75.00),
     *                         @OA\Property(property="total_price", type="number", format="float", example=150.00)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="tickets",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="ticket_number", type="string", example="TIK-2025-000001-001"),
     *                         @OA\Property(property="qr_code", type="string", example="TIK2025000001001QR"),
     *                         @OA\Property(property="status", type="string", enum={"valid", "used", "cancelled"}, example="valid"),
     *                         @OA\Property(property="used_at", type="string", format="date-time", nullable=true, example=null),
     *                         @OA\Property(property="ticket_type", type="string", example="General Admission")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="payment_details",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                     @OA\Property(property="transaction_id", type="string", example="txn_abc123xyz"),
     *                     @OA\Property(property="payment_provider", type="string", example="stripe")
     *                 ),
     *                 @OA\Property(
     *                     property="attendee_info",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="first_name", type="string", example="Jane"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="jane@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+1234567890")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vous n'êtes pas autorisé à voir cette commande.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commande introuvable.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OrderController show method - À implémenter")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id): JsonResponse
    {
        return response()->json([
            'message' => 'OrderController show method - À implémenter'
        ], 501);
    }

    /**
     * @OA\Patch(
     *     path="/api/orders/{id}/cancel",
     *     operationId="cancelOrder",
     *     tags={"Orders"},
     *     summary="Cancel an order",
     *     description="Cancels a pending order and optionally processes refund for paid orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="cancellation_reason", type="string", example="Event cancelled by organizer", description="Reason for cancellation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-2025-000001"),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="cancelled_at", type="string", format="date-time", example="12/09/2025 15:30:00"),
     *                 @OA\Property(property="cancellation_reason", type="string", example="Event cancelled by organizer"),
     *                 @OA\Property(property="refund_status", type="string", enum={"not_applicable", "pending", "processing", "completed"}, example="pending")
     *             ),
     *             @OA\Property(property="message", type="string", example="Commande annulée avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel order",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Impossible d'annuler cette commande")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vous n'êtes pas autorisé à annuler cette commande.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commande introuvable.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OrderController cancel method - À implémenter")
     *         )
     *     )
     * )
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        return response()->json([
            'message' => 'OrderController cancel method - À implémenter'
        ], 501);
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{id}",
     *     operationId="updateOrder",
     *     tags={"Orders"},
     *     summary="Update order information",
     *     description="Updates modifiable order information such as attendee details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="attendee_info",
     *                 type="object",
     *                 description="Updated attendee information",
     *                 @OA\Property(property="first_name", type="string", example="Jane"),
     *                 @OA\Property(property="last_name", type="string", example="Smith"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane.smith@example.com"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="order", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-2025-000001"),
     *                 @OA\Property(property="status", type="string", example="paid"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="12/09/2025 16:30:00"),
     *                 @OA\Property(
     *                     property="attendee_info",
     *                     type="object",
     *                     @OA\Property(property="first_name", type="string", example="Jane"),
     *                     @OA\Property(property="last_name", type="string", example="Smith"),
     *                     @OA\Property(property="email", type="string", example="jane.smith@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+1234567890")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Commande mise à jour avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vous n'êtes pas autorisé à modifier cette commande.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Commande introuvable.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="attendee_info.email",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le format de l'email est invalide.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="OrderController update method - À implémenter")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        return response()->json([
            'message' => 'OrderController update method - À implémenter'
        ], 501);
    }
}