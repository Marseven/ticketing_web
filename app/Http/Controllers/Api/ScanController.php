<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketValidationService;
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
    public function store(Request $request, TicketValidationService $validator): JsonResponse
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

        // Validation unifiée (numérique + physique) via le service commun.
        $r = $validator->validate($request->qr_code, [
            'scanned_by' => $user->id,
            'device_id' => $request->device_id,
            'scanned_at' => $request->scanned_at,
            'location_hint' => $request->location_hint,
            'notes' => $request->notes,
            'metadata' => [
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
            ],
            'enforce_organizer' => true,
            'organizer_ids' => $user->organizers->pluck('id'),
        ]);

        // Accès refusé : ne pas exposer le détail du billet.
        if ($r['result'] === 'forbidden') {
            return response()->json(['message' => $r['message']], 403);
        }

        $httpStatus = match ($r['result']) {
            'valid' => 200,
            'duplicate' => 409,
            default => 400, // invalid, not_found, error
        };

        return response()->json([
            'success' => $r['valid'],
            'message' => $r['message'],
            'result' => $r['result'] === 'not_found' ? 'invalid' : $r['result'],
            'scan_id' => $r['checkin']?->id,
            'source' => $r['source'],
            'ticket' => $r['ticket'],
        ], $httpStatus);
    }
}
