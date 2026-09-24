<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Order;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/guest/tickets/{code}",
     *     summary="Get guest ticket details by code",
     *     tags={"Guest Tickets"},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Ticket code (e.g., TKT-ABC123)",
     *         @OA\Schema(type="string", example="TKT-ABC123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="ticket", type="object",
     *                     @OA\Property(property="code", type="string", example="TKT-ABC123"),
     *                     @OA\Property(property="status", type="string", example="issued"),
     *                     @OA\Property(property="buyer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="buyer_email", type="string", example="john@example.com"),
     *                     @OA\Property(property="qr_code", type="string", description="Base64 encoded QR code"),
     *                     @OA\Property(property="event", type="object",
     *                         @OA\Property(property="title", type="string", example="Festival Électro Gabonais"),
     *                         @OA\Property(property="venue_name", type="string", example="Stade d'Angondjé"),
     *                         @OA\Property(property="date", type="string", example="2025-07-15T20:00:00Z")
     *                     ),
     *                     @OA\Property(property="ticket_type", type="object",
     *                         @OA\Property(property="name", type="string", example="VIP"),
     *                         @OA\Property(property="description", type="string", example="Accès VIP + backstage")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Billet non trouvé")
     *         )
     *     )
     * )
     * 
     * Display guest ticket details
     */
    public function show($code)
    {
        $ticket = Ticket::where('code', $code)
            ->whereHas('order', function($query) {
                $query->where('is_guest_order', true);
            })
            ->with([
                'event:id,title,slug,venue_name',
                'event.schedules' => function($query) {
                    $query->where('status', 'active')->orderBy('starts_at');
                },
                'ticketType:id,name,description',
                'order:id,reference,guest_name,guest_email'
            ])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Billet non trouvé'
            ], 404);
        }

        // Générer le QR code sécurisé
        $qrCodeService = new \App\Services\QRCodeService();
        $qrCodeContent = $qrCodeService->generateTicketQRCode($ticket);

        $ticketData = [
            'code' => $ticket->code,
            'status' => $ticket->status,
            'buyer_name' => $ticket->buyer_name,
            'buyer_email' => $ticket->buyer_email,
            'buyer_phone' => $ticket->buyer_phone,
            'issued_at' => $ticket->issued_at?->toISOString(),
            'used_at' => $ticket->used_at?->toISOString(),
            'qr_code_content' => $qrCodeContent,
            'event' => [
                'title' => $ticket->event->title,
                'slug' => $ticket->event->slug,
                'venue_name' => $ticket->event->venue_name,
                'date' => $ticket->event->schedules->first()?->starts_at,
            ],
            'ticket_type' => [
                'name' => $ticket->ticketType->name,
                'description' => $ticket->ticketType->description,
            ],
            'order' => [
                'reference' => $ticket->order->reference,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => ['ticket' => $ticketData]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/guest/tickets/retrieve/{email}",
     *     summary="Retrieve all tickets for a guest email",
     *     tags={"Guest Tickets"},
     *     @OA\Parameter(
     *         name="email",
     *         in="path",
     *         required=true,
     *         description="Guest email address",
     *         @OA\Schema(type="string", format="email", example="john@example.com")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tickets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tickets", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="code", type="string", example="TKT-ABC123"),
     *                         @OA\Property(property="status", type="string", example="issued"),
     *                         @OA\Property(property="event_title", type="string", example="Festival Électro Gabonais"),
     *                         @OA\Property(property="event_date", type="string", example="2025-07-15T20:00:00Z"),
     *                         @OA\Property(property="order_reference", type="string", example="ORD-A1B2C3D4")
     *                     )
     *                 ),
     *                 @OA\Property(property="total_tickets", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No tickets found for this email",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun billet trouvé pour cette adresse email")
     *         )
     *     )
     * )
     * 
     * Retrieve all tickets for a guest email
     */
    public function retrieve($email)
    {
        // Valider l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse email invalide'
            ], 400);
        }

        $tickets = Ticket::where('buyer_email', $email)
            ->whereHas('order', function($query) {
                $query->where('is_guest_order', true);
            })
            ->with([
                'event:id,title,slug',
                'event.schedules' => function($query) {
                    $query->where('status', 'active')->orderBy('starts_at');
                },
                'order:id,reference'
            ])
            ->orderBy('issued_at', 'desc')
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun billet trouvé pour cette adresse email'
            ], 404);
        }

        $ticketsData = $tickets->map(function($ticket) {
            return [
                'code' => $ticket->code,
                'status' => $ticket->status,
                'issued_at' => $ticket->issued_at?->toISOString(),
                'used_at' => $ticket->used_at?->toISOString(),
                'event_title' => $ticket->event->title,
                'event_slug' => $ticket->event->slug,
                'event_date' => $ticket->event->schedules->first()?->starts_at,
                'order_reference' => $ticket->order->reference,
                'ticket_url' => "/api/guest/tickets/{$ticket->code}",
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tickets' => $ticketsData,
                'total_tickets' => $tickets->count(),
                'guest_email' => $email,
            ]
        ]);
    }

    /**
     * Search tickets by reference, phone or email
     */
    /**
     * QR du billet en data URI (SVG : pas de dépendance imagick).
     */
    private function qrDataUri($ticket): ?string
    {
        return app(\App\Services\TicketQrCode::class)->dataUri($ticket->code);
    }

    public function search(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            // Le nom seul listerait les billets de tous les homonymes : il ne
            // vaut comme critère qu'associé au téléphone.
            'phone' => 'nullable|string|required_with:name',
            'reference' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $query = Ticket::query()->with(['event.schedules', 'event.venue', 'ticketType', 'buyer', 'order', 'schedule']);

        // On ne rend que les billets encore utiles : événement actif, et date
        // pas dépassée de plus d'un jour (le billet reste récupérable le jour J
        // et le lendemain). La date étant lue en base à chaque recherche, un
        // report d'événement prolonge automatiquement la fenêtre.
        $cutoff = now()->subDay();
        $stillUsable = function ($scheduleQuery) use ($cutoff) {
            $scheduleQuery->whereRaw('COALESCE(ends_at, starts_at) >= ?', [$cutoff]);
        };

        // Recherche par référence (code du ticket OU référence de la commande)
        if ($request->filled('reference')) {
            $ref = $request->input('reference');
            $query->where(function($q) use ($ref) {
                $q->where('code', 'LIKE', "%{$ref}%")
                  ->orWhereHas('order', function($orderQuery) use ($ref) {
                      $orderQuery->where('reference', 'LIKE', "%{$ref}%");
                  });
            });
        }

        // Recherche par téléphone : compte, commande ou paiement. La règle est
        // portée par `Ticket::scopeForPhone` pour que l'administration
        // cherche exactement de la même façon.
        if ($request->filled('phone')) {
            $query->forPhone($request->input('phone'));
        }

        // Recherche par nom : chaque mot saisi doit se retrouver dans le nom du
        // compte ou celui laissé à la commande, dans n'importe quel ordre
        // (« Leofa Abila » trouve « Abila Leofa »).
        // Même règle que dans l'administration : le nom vit sur le compte ou
        // sur la commande invité, et les mots comptent dans n'importe quel
        // ordre. Voir `Ticket::scopeForName`.
        if ($request->filled('name')) {
            $query->forName($request->input('name'));
        }

        // Recherche par email
        if ($request->filled('email')) {
            $email = $request->input('email');
            $query->where(function($q) use ($email) {
                $q->whereHas('buyer', function($buyerQuery) use ($email) {
                    $buyerQuery->where('email', 'LIKE', "%{$email}%");
                })
                ->orWhereHas('order', function($orderQuery) use ($email) {
                    $orderQuery->where('guest_email', 'LIKE', "%{$email}%");
                });
            });
        }

        // La fenêtre de dates s'applique en DERNIER : le repli « événement
        // passé » doit porter sur les mêmes critères de nom et de numéro, sinon
        // il répondrait oui pour n'importe quelle recherche.
        $withoutDateWindow = (clone $query);

        $query->whereHas('event', function ($eventQuery) {
            $eventQuery->where('is_active', true)->where('status', '!=', 'cancelled');
        })->where(function ($q) use ($stillUsable) {
            $q->whereHas('schedule', $stillUsable)
              ->orWhere(function ($undated) use ($stillUsable) {
                  // Billet sans date propre : on se rabat sur les dates de l'événement.
                  $undated->whereNull('schedule_id')->whereHas('event.schedules', $stillUsable);
              });
        });

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            // Le billet existe-t-il, mais pour un événement déjà passé ?
            $past = $withoutDateWindow->latest('id')->first();

            if ($past) {
                $date = $past->schedule?->starts_at ?? $past->event?->schedules->first()?->starts_at;

                return response()->json([
                    'success' => false,
                    'error_code' => 'EVENT_OVER',
                    'message' => $date
                        ? 'Ce billet concerne un événement déjà passé (' . $date->locale('fr')->isoFormat('D MMMM YYYY') . '). Les billets restent accessibles jusqu\'au lendemain de l\'événement.'
                        : 'Ce billet concerne un événement déjà passé.',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'error_code' => 'NOT_FOUND',
                'message' => 'Aucun ticket trouvé avec ces critères de recherche'
            ], 404);
        }

        $ticketsData = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'status' => $ticket->status,
                'issued_at' => $ticket->issued_at,
                'used_at' => $ticket->used_at,
                // Image du QR générée ici (même SVG que le PDF) : le front
                // n'a plus besoin d'un service tiers pour afficher le billet.
                'qr_code' => $this->qrDataUri($ticket),
                'event' => [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'slug' => $ticket->event->slug,
                    'venue_name' => $ticket->event->venue?->name,
                    'image_url' => $ticket->event->image,
                ],
                'schedule' => [
                    // La date du billet, pas la première de l'événement : un
                    // événement multi-dates en a plusieurs.
                    'starts_at' => $ticket->schedule?->starts_at ?? $ticket->event->schedules->first()?->starts_at,
                ],
                'ticket_type' => [
                    'name' => $ticket->ticketType->name,
                    'price' => $ticket->ticketType->price,
                ],
                'buyer' => [
                    'name' => $ticket->buyer?->name ?? $ticket->order->guest_name,
                    'email' => $ticket->buyer?->email ?? $ticket->order->guest_email,
                    'phone' => $ticket->buyer?->phone ?? $ticket->order->guest_phone,
                ],
                'order' => [
                    'reference' => $ticket->order->reference,
                    'total_amount' => $ticket->order->total_amount,
                    'status' => $ticket->order->status,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tickets' => $ticketsData,
                'total' => $tickets->count(),
            ]
        ]);
    }
}