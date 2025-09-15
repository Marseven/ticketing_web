<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Checkin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Tickets",
 *     description="API endpoints for ticket validation and management"
 * )
 */
class TicketController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/tickets/validate",
     *     operationId="validateTicket",
     *     tags={"Tickets"},
     *     summary="Validate a ticket via QR code",
     *     description="Validates a ticket by scanning its QR code. Supports both secure EMVCO/AMA format and fallback to simple codes.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="QR code validation request",
     *         @OA\JsonContent(
     *             required={"qr_code"},
     *             @OA\Property(
     *                 property="qr_code",
     *                 type="string",
     *                 description="The QR code content to validate",
     *                 example="TCK-2024-001-ABC123"
     *             ),
     *             @OA\Property(
     *                 property="action",
     *                 type="string",
     *                 enum={"validate", "info"},
     *                 description="Action to perform - validate or just get info",
     *                 example="validate"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket validation successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ticket validé avec succès"),
     *             @OA\Property(property="result", type="string", example="valid"),
     *             @OA\Property(
     *                 property="ticket",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="TCK-2024-001-ABC123"),
     *                 @OA\Property(property="status", type="string", example="issued"),
     *                 @OA\Property(
     *                     property="event",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Concert Example"),
     *                     @OA\Property(property="slug", type="string", example="concert-example"),
     *                     @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg")
     *                 ),
     *                 @OA\Property(
     *                     property="ticket_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="VIP"),
     *                     @OA\Property(property="description", type="string", example="VIP access with premium amenities")
     *                 ),
     *                 @OA\Property(
     *                     property="buyer",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="issued_at", type="string", example="01/01/2024 10:00:00"),
     *                 @OA\Property(property="used_at", type="string", nullable=true, example=null),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="starts_at", type="string", example="01/01/2024 20:00:00"),
     *                     @OA\Property(property="ends_at", type="string", example="01/01/2024 23:00:00"),
     *                     @OA\Property(property="door_time", type="string", nullable=true, example="01/01/2024 19:30:00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed - Invalid, duplicate, or not yet valid ticket",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ticket déjà utilisé"),
     *             @OA\Property(property="result", type="string", enum={"invalid", "duplicate"}, example="duplicate"),
     *             @OA\Property(property="first_scan", type="string", example="01/01/2024 20:30:00"),
     *             @OA\Property(property="status", type="string", example="used"),
     *             @OA\Property(property="event_start", type="string", example="01/01/2024 20:00:00"),
     *             @OA\Property(property="security_check", type="string", example="PASSED"),
     *             @OA\Property(
     *                 property="ticket",
     *                 type="object",
     *                 description="Ticket information (included for duplicate tickets)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="qr_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The qr code field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="System error during validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur système lors de la validation"),
     *             @OA\Property(property="result", type="string", example="error")
     *         )
     *     )
     * )
     * Valider un ticket via QR code
     */
    public function validateTicket(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string',
            'action' => 'nullable|string|in:validate,info'
        ]);

        $qrCode = $request->qr_code;
        $action = $request->action ?? 'validate';

        try {
            DB::beginTransaction();

            // Utiliser le service QR Code sécurisé pour la validation
            $qrService = new \App\Services\QRCodeService();
            
            // Tenter d'abord la validation sécurisée EMVCO/AMA
            $secureValidation = $qrService->validateTicketFromQRCode($qrCode);
            
            if ($secureValidation['valid']) {
                $ticket = $secureValidation['ticket'];
                $ticket->load(['event', 'ticketType', 'buyer', 'schedule']);
                
                Log::info('QR Code sécurisé validé avec succès', [
                    'ticket_id' => $ticket->id,
                    'security_check' => 'PASSED'
                ]);
            } else {
                // Fallback vers l'ancienne méthode (code simple)
                $ticket = Ticket::with(['event', 'ticketType', 'buyer', 'schedule'])
                               ->byCode($qrCode)
                               ->first();
                
                if (!$ticket) {
                    $this->logCheckin(null, $request, 'invalid', 'QR code non trouvé: ' . ($secureValidation['message'] ?? 'Code simple invalide'));
                    
                    return response()->json([
                        'valid' => false,
                        'message' => 'QR code invalide: ' . ($secureValidation['message'] ?? 'Non trouvé'),
                        'result' => 'invalid',
                        'security_check' => $secureValidation['error'] ?? 'NOT_FOUND'
                    ], 400);
                }
                
                Log::warning('QR Code simple utilisé (fallback)', [
                    'ticket_id' => $ticket->id,
                    'security_check' => 'FALLBACK',
                    'reason' => $secureValidation['message'] ?? 'Décodage sécurisé échoué'
                ]);
            }

            // Vérifier si le ticket a déjà été scanné
            $existingCheckin = $ticket->checkins()
                                    ->where('result', 'valid')
                                    ->first();

            if ($existingCheckin) {
                $this->logCheckin($ticket, $request, 'duplicate', 'Ticket déjà utilisé');
                
                return response()->json([
                    'valid' => false,
                    'message' => 'Ticket déjà utilisé',
                    'result' => 'duplicate',
                    'first_scan' => $existingCheckin->scanned_at->format('d/m/Y H:i:s'),
                    'ticket' => $this->formatTicketInfo($ticket)
                ], 400);
            }

            // Vérifier le statut du ticket
            if (!$ticket->canBeScanned()) {
                $this->logCheckin($ticket, $request, 'invalid', 'Ticket non valide (statut: ' . $ticket->status . ')');
                
                return response()->json([
                    'valid' => false,
                    'message' => 'Ticket non valide',
                    'result' => 'invalid',
                    'status' => $ticket->status
                ], 400);
            }

            // Vérifier si l'événement est dans la bonne période
            if ($ticket->schedule && $ticket->schedule->starts_at > now()) {
                $this->logCheckin($ticket, $request, 'invalid', 'Événement pas encore commencé');
                
                return response()->json([
                    'valid' => false,
                    'message' => 'L\'événement n\'a pas encore commencé',
                    'result' => 'invalid',
                    'event_start' => $ticket->schedule->starts_at->format('d/m/Y H:i:s')
                ], 400);
            }

            // Ticket valide - marquer comme utilisé
            $ticket->markAsUsed();
            
            // Enregistrer le scan
            $this->logCheckin($ticket, $request, 'valid', 'Ticket validé avec succès');

            DB::commit();

            return response()->json([
                'valid' => true,
                'message' => 'Ticket validé avec succès',
                'result' => 'valid',
                'ticket' => $this->formatTicketInfo($ticket)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur validation ticket', [
                'qr_code' => $request->qr_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Erreur système lors de la validation',
                'result' => 'error'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/{code}",
     *     operationId="showTicket",
     *     tags={"Tickets"},
     *     summary="Get ticket information by code",
     *     description="Retrieves detailed information about a ticket using its code, including checkin history.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="The ticket code to retrieve",
     *         @OA\Schema(
     *             type="string",
     *             example="TCK-2024-001-ABC123"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="ticket",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="TCK-2024-001-ABC123"),
     *                 @OA\Property(property="status", type="string", example="issued"),
     *                 @OA\Property(
     *                     property="event",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Concert Example"),
     *                     @OA\Property(property="slug", type="string", example="concert-example"),
     *                     @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg")
     *                 ),
     *                 @OA\Property(
     *                     property="ticket_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="VIP"),
     *                     @OA\Property(property="description", type="string", example="VIP access with premium amenities")
     *                 ),
     *                 @OA\Property(
     *                     property="buyer",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="issued_at", type="string", example="01/01/2024 10:00:00"),
     *                 @OA\Property(property="used_at", type="string", nullable=true, example="01/01/2024 20:30:00"),
     *                 @OA\Property(
     *                     property="schedule",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="starts_at", type="string", example="01/01/2024 20:00:00"),
     *                     @OA\Property(property="ends_at", type="string", example="01/01/2024 23:00:00"),
     *                     @OA\Property(property="door_time", type="string", nullable=true, example="01/01/2024 19:30:00")
     *                 ),
     *                 @OA\Property(
     *                     property="checkins",
     *                     type="array",
     *                     description="Checkin history for this ticket",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="scanned_at", type="string", example="01/01/2024 20:30:00"),
     *                         @OA\Property(property="result", type="string", enum={"valid", "invalid", "duplicate"}, example="valid"),
     *                         @OA\Property(property="device_id", type="string", nullable=true, example="SCANNER-001")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ticket non trouvé")
     *         )
     *     )
     * )
     * Récupérer les informations d'un ticket
     */
    public function show(Request $request, $code): JsonResponse
    {
        $ticket = Ticket::with(['event', 'ticketType', 'buyer', 'schedule', 'checkins'])
                       ->byCode($code)
                       ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket non trouvé'
            ], 404);
        }

        return response()->json([
            'ticket' => $this->formatTicketInfo($ticket, true)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/retrieve/{token}",
     *     operationId="retrieveTickets",
     *     tags={"Tickets"},
     *     summary="Retrieve tickets using recovery token",
     *     description="Retrieves all tickets for a specific order using a recovery token. Token format: base64(email:order_reference)",
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Base64 encoded recovery token containing email and order reference",
     *         @OA\Schema(
     *             type="string",
     *             example="am9obkBleGFtcGxlLmNvbTpPUkQtMjAyNC0wMDE="
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tickets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="tickets",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="TCK-2024-001-ABC123"),
     *                     @OA\Property(property="status", type="string", example="issued"),
     *                     @OA\Property(
     *                         property="event",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Concert Example"),
     *                         @OA\Property(property="slug", type="string", example="concert-example"),
     *                         @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg")
     *                     ),
     *                     @OA\Property(
     *                         property="ticket_type",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="VIP"),
     *                         @OA\Property(property="description", type="string", example="VIP access with premium amenities")
     *                     ),
     *                     @OA\Property(
     *                         property="buyer",
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     ),
     *                     @OA\Property(property="issued_at", type="string", example="01/01/2024 10:00:00"),
     *                     @OA\Property(property="used_at", type="string", nullable=true, example=null),
     *                     @OA\Property(
     *                         property="schedule",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="starts_at", type="string", example="01/01/2024 20:00:00"),
     *                         @OA\Property(property="ends_at", type="string", example="01/01/2024 23:00:00"),
     *                         @OA\Property(property="door_time", type="string", nullable=true, example="01/01/2024 19:30:00")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid recovery token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No tickets found for the given token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Aucun ticket trouvé")
     *         )
     *     )
     * )
     * Récupérer un ticket via token de récupération
     */
    public function retrieve(Request $request, $token): JsonResponse
    {
        // Token format: base64(email:order_reference)
        try {
            $decoded = base64_decode($token);
            [$email, $reference] = explode(':', $decoded);

            $tickets = Ticket::with(['event', 'ticketType', 'order', 'schedule'])
                           ->whereHas('order', function($query) use ($reference) {
                               $query->where('reference', $reference);
                           })
                           ->whereHas('buyer', function($query) use ($email) {
                               $query->where('email', $email);
                           })
                           ->get();

            if ($tickets->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun ticket trouvé'
                ], 404);
            }

            return response()->json([
                'tickets' => $tickets->map(function($ticket) {
                    return $this->formatTicketInfo($ticket);
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token invalide'
            ], 400);
        }
    }

    /**
     * Enregistrer un checkin
     */
    private function logCheckin(?Ticket $ticket, Request $request, string $result, string $message): void
    {
        $checkinData = [
            'ticket_id' => $ticket?->id,
            'scanned_by' => auth()->id() ?? 1, // Utilisateur qui scanne
            'device_id' => $request->header('X-Device-ID'),
            'scanned_at' => now(),
            'result' => $result,
            'location_hint' => $request->ip(),
        ];

        Checkin::create($checkinData);

        Log::info('Ticket scan', [
            'ticket_id' => $ticket?->id,
            'qr_code' => $request->qr_code ?? $ticket?->code,
            'result' => $result,
            'message' => $message,
            'scanned_by' => auth()->id(),
            'ip' => $request->ip()
        ]);
    }

    /**
     * Formater les informations du ticket
     */
    private function formatTicketInfo(Ticket $ticket, bool $detailed = false): array
    {
        $data = [
            'id' => $ticket->id,
            'code' => $ticket->code,
            'status' => $ticket->status,
            'event' => [
                'id' => $ticket->event->id,
                'title' => $ticket->event->title,
                'slug' => $ticket->event->slug,
                'image_url' => $ticket->event->image_url,
            ],
            'ticket_type' => [
                'id' => $ticket->ticketType->id,
                'name' => $ticket->ticketType->name,
                'description' => $ticket->ticketType->description,
            ],
            'buyer' => [
                'name' => $ticket->buyer->name,
                'email' => $ticket->buyer->email,
            ],
            'issued_at' => $ticket->issued_at?->format('d/m/Y H:i:s'),
            'used_at' => $ticket->used_at?->format('d/m/Y H:i:s'),
        ];

        if ($ticket->schedule) {
            $data['schedule'] = [
                'starts_at' => $ticket->schedule->starts_at->format('d/m/Y H:i:s'),
                'ends_at' => $ticket->schedule->ends_at->format('d/m/Y H:i:s'),
                'door_time' => $ticket->schedule->door_time?->format('d/m/Y H:i:s'),
            ];
        }

        if ($detailed) {
            $data['checkins'] = $ticket->checkins->map(function($checkin) {
                return [
                    'scanned_at' => $checkin->scanned_at->format('d/m/Y H:i:s'),
                    'result' => $checkin->result,
                    'device_id' => $checkin->device_id,
                ];
            });
        }

        return $data;
    }
}