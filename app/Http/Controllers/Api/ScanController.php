<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Scanning",
 *     description="API Endpoints for ticket scanning and validation"
 * )
 */
class ScanController extends Controller
{
    /**
     * Record a ticket scan from mobile app
     * 
     * @OA\Post(
     *     path="/api/scans",
     *     operationId="recordTicketScan",
     *     tags={"Scanning"},
     *     summary="Record ticket scan",
     *     description="Records a ticket scan from mobile scanning app. Validates the ticket and updates its status.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_code", "scanned_at", "device_id"},
     *             @OA\Property(
     *                 property="qr_code",
     *                 type="string",
     *                 description="QR code content from the scanned ticket",
     *                 example="TKT123456789"
     *             ),
     *             @OA\Property(
     *                 property="scanned_at",
     *                 type="string",
     *                 format="date-time",
     *                 description="Timestamp when the ticket was scanned",
     *                 example="2025-06-15T20:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="device_id",
     *                 type="string",
     *                 description="Unique identifier of the scanning device",
     *                 example="SCANNER_001_GATE_A"
     *             ),
     *             @OA\Property(
     *                 property="location_hint",
     *                 type="string",
     *                 description="Optional location hint where scan occurred",
     *                 example="Main Entrance Gate A",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="notes",
     *                 type="string",
     *                 description="Optional notes about the scan",
     *                 example="Customer had damaged ticket",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Valid ticket scan recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Billet validé avec succès"),
     *             @OA\Property(property="result", type="string", enum={"valid", "duplicate", "invalid"}, example="valid"),
     *             @OA\Property(property="scan_id", type="integer", example=123),
     *             @OA\Property(
     *                 property="ticket",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="TKT123456789"),
     *                 @OA\Property(property="status", type="string", enum={"issued", "used"}, example="used"),
     *                 @OA\Property(
     *                     property="event",
     *                     type="object",
     *                     @OA\Property(property="title", type="string", example="Summer Music Festival"),
     *                     @OA\Property(property="venue_name", type="string", example="Central Park")
     *                 ),
     *                 @OA\Property(
     *                     property="ticket_type",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="VIP Access")
     *                 ),
     *                 @OA\Property(
     *                     property="holder",
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="used_at", type="string", nullable=true, example="15/06/2025 20:30:15")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid QR code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="QR code invalide"),
     *             @OA\Property(property="result", type="string", example="invalid"),
     *             @OA\Property(property="scan_id", type="integer", example=124)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Seuls les organisateurs peuvent enregistrer des scans.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate ticket scan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce billet a déjà été scanné le 15/06/2025 à 18:45:30"),
     *             @OA\Property(property="result", type="string", example="duplicate"),
     *             @OA\Property(property="scan_id", type="integer", example=125),
     *             @OA\Property(
     *                 property="ticket",
     *                 type="object",
     *                 description="Ticket information with duplicate status"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The qr code field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="qr_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The qr code field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="scanned_at",
     *                     type="array",
     *                     @OA\Items(type="string", example="The scanned at field is required.")
     *                 ),
     *                 @OA\Property(
     *                     property="device_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The device id field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => 'required|string',
            'scanned_at' => 'required|date',
            'device_id' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'message' => 'Seuls les organisateurs peuvent enregistrer des scans.',
            ], 403);
        }

        // Trouver le ticket
        $ticket = Ticket::where('code', $request->qr_code)->first();
        
        if (!$ticket) {
            // Enregistrer le scan invalide
            $checkin = Checkin::create([
                'ticket_id' => null,
                'scanned_by' => $user->id,
                'device_id' => $request->device_id,
                'result' => 'invalid',
                'scanned_at' => $request->scanned_at,
                'notes' => 'QR code non trouvé dans la base de données',
                'metadata' => [
                    'qr_code' => $request->qr_code,
                    'user_agent' => $request->header('User-Agent'),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'QR code invalide',
                'result' => 'invalid',
                'scan_id' => $checkin->id,
            ], 400);
        }

        // Vérifier que l'organisateur a accès à cet événement
        $organizerIds = $user->organizers->pluck('id');
        if (!$organizerIds->contains($ticket->event->organizer_id)) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à cet événement.',
            ], 403);
        }

        // Vérifier si c'est un duplicate
        $existingCheckin = Checkin::where('ticket_id', $ticket->id)
                                 ->where('result', 'valid')
                                 ->first();

        $result = $existingCheckin ? 'duplicate' : 'valid';
        $message = $existingCheckin 
            ? 'Ce billet a déjà été scanné le ' . $existingCheckin->scanned_at->format('d/m/Y à H:i:s')
            : 'Billet validé avec succès';

        // Marquer le ticket comme utilisé si c'est un scan valide
        if ($result === 'valid') {
            $ticket->update([
                'status' => 'used',
                'used_at' => $request->scanned_at,
            ]);
        }

        // Enregistrer le checkin
        $checkin = Checkin::create([
            'ticket_id' => $ticket->id,
            'scanned_by' => $user->id,
            'device_id' => $request->device_id,
            'result' => $result,
            'scanned_at' => $request->scanned_at,
            'location_hint' => $request->location_hint,
            'notes' => $request->notes,
            'metadata' => [
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'existing_checkin_id' => $existingCheckin?->id,
            ],
        ]);

        return response()->json([
            'success' => $result === 'valid',
            'message' => $message,
            'result' => $result,
            'scan_id' => $checkin->id,
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'status' => $ticket->status,
                'event' => [
                    'title' => $ticket->event->title,
                    'venue_name' => $ticket->event->venue_name,
                ],
                'ticket_type' => [
                    'name' => $ticket->ticketType->name,
                ],
                'holder' => [
                    'name' => $ticket->buyer_name,
                    'email' => $ticket->buyer_email,
                ],
                'used_at' => $ticket->used_at?->format('d/m/Y H:i:s'),
            ],
        ], $result === 'valid' ? 200 : 409);
    }
}
