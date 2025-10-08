<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/guest/orders",
     *     summary="Create a guest order (no authentication required)",
     *     tags={"Guest Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_slug", "ticket_type_id", "quantity", "guest_name", "guest_email"},
     *             @OA\Property(property="event_slug", type="string", example="festival-electro-gabonais", description="Event slug identifier"),
     *             @OA\Property(property="ticket_type_id", type="integer", example=1, description="Ticket type ID"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, maximum=10, example=2, description="Number of tickets to purchase"),
     *             @OA\Property(property="guest_name", type="string", maxLength=255, example="John Doe", description="Guest full name"),
     *             @OA\Property(property="guest_email", type="string", format="email", example="john@example.com", description="Guest email address"),
     *             @OA\Property(property="guest_phone", type="string", pattern="^\\+?[0-9]{8,15}$", example="+241123456789", description="Guest phone number (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commande créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="reference", type="string", example="ORD-A1B2C3D4"),
     *                     @OA\Property(property="guest_name", type="string", example="John Doe"),
     *                     @OA\Property(property="guest_email", type="string", example="john@example.com"),
     *                     @OA\Property(property="total_amount", type="number", example=25000),
     *                     @OA\Property(property="currency", type="string", example="XAF"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="tickets_count", type="integer", example=2),
     *                     @OA\Property(property="event", type="object",
     *                         @OA\Property(property="title", type="string", example="Festival Électro Gabonais"),
     *                         @OA\Property(property="slug", type="string", example="festival-electro-gabonais")
     *                     )
     *                 ),
     *                 @OA\Property(property="payment_url", type="string", example="/payment/ORD-A1B2C3D4", description="URL to proceed with payment")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - validation error or business rule violation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Event or ticket type not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Événement non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données de validation échouées"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Store a new guest order
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'event_slug' => 'required|string|exists:events,slug',
            'ticket_type_id' => 'required|integer|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:10',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'nullable|string|regex:/^\+?[0-9]{8,15}$/',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer l'événement
            $event = Event::where('slug', $validated['event_slug'])
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
            // Calculer le nombre de billets vendus
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

            // Créer ou récupérer un utilisateur guest
            $guestUser = $this->createOrGetGuestUser(
                $validated['guest_name'],
                $validated['guest_email'],
                $validated['guest_phone'] ?? null
            );

            // Calculer les montants
            $unitPrice = $ticketType->price ?? 0;
            $subtotalAmount = $unitPrice * $validated['quantity'];
            $feesAmount = $this->calculateFees($subtotalAmount);
            $taxAmount = $this->calculateTaxes($subtotalAmount);
            $totalAmount = $subtotalAmount + $feesAmount + $taxAmount;

            // Créer la commande
            $order = Order::create([
                'organizer_id' => $event->organizer_id,
                'buyer_id' => $guestUser->id,
                'currency' => 'XAF', // Franc CFA d'Afrique Centrale (CEMAC)
                'subtotal_amount' => $subtotalAmount,
                'fees_amount' => $feesAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'reference' => $this->generateOrderReference(),
                'placed_at' => now(),
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'],
                'guest_phone' => $validated['guest_phone'] ?? null,
                'is_guest_order' => true,
            ]);

            // Créer les billets
            for ($i = 0; $i < $validated['quantity']; $i++) {
                Ticket::create([
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketType->id,
                    'schedule_id' => $event->schedules->where('status', 'active')->first()?->id,
                    'buyer_id' => $guestUser->id,
                    'code' => $this->generateTicketCode(),
                    'status' => 'issued',
                    'issued_at' => now(),
                ]);
            }

            // Note: Le nombre de billets vendus est calculé dynamiquement en comptant les tickets
            // avec status 'issued' ou 'used', pas besoin de maintenir un compteur

            DB::commit();

            // Préparer la réponse
            $orderData = [
                'id' => $order->id,
                'reference' => $order->reference,
                'guest_name' => $order->guest_name,
                'guest_email' => $order->guest_email,
                'guest_phone' => $order->guest_phone,
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
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/guest/orders/{reference}",
     *     summary="Get guest order details by reference",
     *     tags={"Guest Orders"},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Order reference (e.g., ORD-A1B2C3D4)",
     *         @OA\Schema(type="string", example="ORD-A1B2C3D4")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order", type="object",
     *                     @OA\Property(property="reference", type="string", example="ORD-A1B2C3D4"),
     *                     @OA\Property(property="guest_name", type="string", example="John Doe"),
     *                     @OA\Property(property="guest_email", type="string", example="john@example.com"),
     *                     @OA\Property(property="total_amount", type="number", example=25000),
     *                     @OA\Property(property="status", type="string", example="paid"),
     *                     @OA\Property(property="tickets", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="code", type="string", example="TKT-ABC123"),
     *                             @OA\Property(property="status", type="string", example="issued"),
     *                             @OA\Property(property="qr_code_url", type="string", example="/qr/TKT-ABC123")
     *                         )
     *                     ),
     *                     @OA\Property(property="event", type="object",
     *                         @OA\Property(property="title", type="string", example="Festival Électro Gabonais"),
     *                         @OA\Property(property="date", type="string", example="2025-07-15T20:00:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Commande non trouvée")
     *         )
     *     )
     * )
     * 
     * Display guest order details
     */
    public function show($reference)
    {
        $order = Order::where('reference', $reference)
            ->where('is_guest_order', true)
            ->with([
                'tickets' => function($query) {
                    $query->select('id', 'order_id', 'event_id', 'ticket_type_id', 'code', 'status', 'issued_at', 'used_at')
                          ->with(['event:id,title,slug', 'ticketType:id,name']);
                }
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Récupérer l'événement du premier ticket
        $firstTicket = $order->tickets->first();
        $event = $firstTicket ? $firstTicket->event : null;

        $orderData = [
            'reference' => $order->reference,
            'guest_name' => $order->guest_name,
            'guest_email' => $order->guest_email,
            'guest_phone' => $order->guest_phone,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => $order->status,
            'quantity' => $order->tickets->count(),
            'placed_at' => $order->placed_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
            'tickets' => $order->tickets->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'code' => $ticket->code,
                    'status' => $ticket->status,
                    'issued_at' => $ticket->issued_at?->toISOString(),
                    'used_at' => $ticket->used_at?->toISOString(),
                    'qr_code_url' => "/api/guest/tickets/{$ticket->code}",
                    'ticket_type' => [
                        'id' => $ticket->ticketType?->id,
                        'name' => $ticket->ticketType?->name,
                    ]
                ];
            }),
            'event' => $event ? [
                'title' => $event->title,
                'slug' => $event->slug,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => ['order' => $orderData]
        ]);
    }

    /**
     * Create or get a guest user
     */
    private function createOrGetGuestUser(string $name, string $email, ?string $phone = null): User
    {
        // Chercher un utilisateur existant avec cet email
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Créer un nouvel utilisateur guest
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make(Str::random(32)), // Mot de passe aléatoire
                'email_verified_at' => now(), // Auto-vérifié pour les guests
                'is_organizer' => false,
                'status' => 'active',
                'is_guest' => true,
            ]);
        } else {
            // Mettre à jour les informations si nécessaire
            $user->update([
                'name' => $name,
                'phone' => $phone ?: $user->phone,
            ]);
        }

        return $user;
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
            $reference = 'ORD-' . strtoupper(Str::random(8));
        } while (Order::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate unique ticket code
     */
    private function generateTicketCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (Ticket::where('code', $code)->exists());

        return $code;
    }
}