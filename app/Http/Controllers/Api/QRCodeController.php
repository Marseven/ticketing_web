<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="QR Codes",
 *     description="API Endpoints for QR Code generation and management"
 * )
 */
class QRCodeController extends Controller
{
    protected QRCodeService $qrService;

    public function __construct(QRCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Generate a secure QR Code for a ticket
     * 
     * @OA\Get(
     *     path="/api/tickets/{ticketId}/qr-code",
     *     operationId="generateSecureQRCode",
     *     tags={"QR Codes"},
     *     summary="Generate secure QR Code for a ticket",
     *     description="Generates a secure QR Code for a specific ticket with EMVCO/AMA compliance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         required=true,
     *         description="ID of the ticket",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR Code generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="ticket_id", type="integer", example=1),
     *                 @OA\Property(property="secure_qr_content", type="string", example="00020101021243650016A000000025010..."),
     *                 @OA\Property(property="simple_qr_code", type="string", example="TKT123456789"),
     *                 @OA\Property(property="qr_format", type="string", example="EMVCO_AMA_COMPLIANT"),
     *                 @OA\Property(property="security_level", type="string", example="HIGH"),
     *                 @OA\Property(property="generated_at", type="string", format="date-time", example="2025-06-15T14:30:00.000Z"),
     *                 @OA\Property(
     *                     property="ticket_info",
     *                     type="object",
     *                     @OA\Property(property="code", type="string", example="TKT123456789"),
     *                     @OA\Property(property="status", type="string", enum={"issued", "used"}, example="issued"),
     *                     @OA\Property(
     *                         property="event",
     *                         type="object",
     *                         @OA\Property(property="title", type="string", example="Summer Music Festival"),
     *                         @OA\Property(property="venue", type="string", example="Central Park"),
     *                         @OA\Property(property="city", type="string", example="New York")
     *                     ),
     *                     @OA\Property(
     *                         property="ticket_type",
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="VIP Access")
     *                     ),
     *                     @OA\Property(
     *                         property="holder",
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="QR Code sécurisé généré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid ticket state",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce billet n'est pas dans un état valide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès refusé à ce billet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Ticket]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="QR Code generation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la génération du QR Code: ...")
     *         )
     *     )
     * )
     */
    public function generateSecureQR(Request $request, $ticketId): JsonResponse
    {
        $user = $request->user();
        
        $ticket = Ticket::with(['event', 'ticketType', 'buyer'])
                       ->findOrFail($ticketId);

        // Vérifier que l'utilisateur a accès à ce billet
        if ($ticket->buyer_id !== $user->id && !$user->is_organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé à ce billet'
            ], 403);
        }

        // Vérifier que le billet est dans un état valide
        if (!in_array($ticket->status, ['issued', 'used'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce billet n\'est pas dans un état valide'
            ], 400);
        }

        try {
            // Générer le contenu QR sécurisé
            $secureQRContent = $this->qrService->generateTicketQRCode($ticket);
            
            // Générer aussi le QR simple pour compatibilité
            $simpleQRCode = $ticket->code;

            return response()->json([
                'success' => true,
                'data' => [
                    'ticket_id' => $ticket->id,
                    'secure_qr_content' => $secureQRContent,
                    'simple_qr_code' => $simpleQRCode,
                    'qr_format' => 'EMVCO_AMA_COMPLIANT',
                    'security_level' => 'HIGH',
                    'generated_at' => now()->toISOString(),
                    'ticket_info' => [
                        'code' => $ticket->code,
                        'status' => $ticket->status,
                        'event' => [
                            'title' => $ticket->event->title,
                            'venue' => $ticket->event->venue_name,
                            'city' => $ticket->event->venue_city,
                        ],
                        'ticket_type' => [
                            'name' => $ticket->ticketType->name,
                        ],
                        'holder' => [
                            'name' => $ticket->buyer_name,
                            'email' => $ticket->buyer_email,
                        ]
                    ]
                ],
                'message' => 'QR Code sécurisé généré avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du QR Code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze and decode a QR Code for debugging/testing purposes
     * 
     * @OA\Post(
     *     path="/api/qr-codes/analyze",
     *     operationId="analyzeQRCode",
     *     tags={"QR Codes"},
     *     summary="Analyze QR Code content",
     *     description="Decodes and analyzes QR Code content for debugging and testing purposes. Only accessible to organizers.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_content"},
     *             @OA\Property(
     *                 property="qr_content",
     *                 type="string",
     *                 description="QR Code content to analyze",
     *                 example="00020101021243650016A000000025010..."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR Code analysis completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="decoded_data",
     *                     type="object",
     *                     @OA\Property(property="valid", type="boolean", example=true),
     *                     @OA\Property(property="security_hash", type="string", example="abc123..."),
     *                     @OA\Property(
     *                         property="parsed_data",
     *                         type="object",
     *                         @OA\Property(property="00", type="string", example="01"),
     *                         @OA\Property(property="01", type="string", example="12")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="validation_result",
     *                     type="object",
     *                     description="Validation result from QR service"
     *                 ),
     *                 @OA\Property(
     *                     property="analysis",
     *                     type="object",
     *                     @OA\Property(property="format_valid", type="boolean", example=true),
     *                     @OA\Property(property="crc_valid", type="boolean", example=true),
     *                     @OA\Property(property="structure_compliant", type="boolean", example=true),
     *                     @OA\Property(property="security_hash_present", type="boolean", example=true),
     *                     @OA\Property(property="emvco_compliant", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Analyse du QR Code terminée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Analysis error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'analyse: ..."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="raw_content", type="string"),
     *                 @OA\Property(property="content_length", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seuls les organisateurs peuvent analyser les QR Codes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The qr content field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="qr_content",
     *                     type="array",
     *                     @OA\Items(type="string", example="The qr content field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function analyzeQRCode(Request $request): JsonResponse
    {
        $request->validate([
            'qr_content' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les organisateurs peuvent analyser les QR Codes'
            ], 403);
        }

        try {
            $qrContent = $request->qr_content;
            
            // Décoder le QR Code
            $decodedData = $this->qrService->decodeTicketQRCode($qrContent);
            
            // Validation complète
            $validation = $this->qrService->validateTicketFromQRCode($qrContent);

            return response()->json([
                'success' => true,
                'data' => [
                    'decoded_data' => $decodedData,
                    'validation_result' => $validation,
                    'analysis' => [
                        'format_valid' => $decodedData['valid'],
                        'crc_valid' => !isset($decodedData['error']) || $decodedData['error'] !== 'CRC_MISMATCH',
                        'structure_compliant' => $decodedData['valid'] && isset($decodedData['parsed_data']),
                        'security_hash_present' => isset($decodedData['security_hash']),
                        'emvco_compliant' => $decodedData['valid'] && isset($decodedData['parsed_data']['00']),
                    ]
                ],
                'message' => 'Analyse du QR Code terminée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage(),
                'data' => [
                    'raw_content' => $request->qr_content,
                    'content_length' => strlen($request->qr_content),
                ]
            ], 400);
        }
    }

    /**
     * Compare simple vs secure QR Code formats for demonstration purposes
     * 
     * @OA\Get(
     *     path="/api/tickets/{ticketId}/qr-codes/compare",
     *     operationId="compareQRFormats",
     *     tags={"QR Codes"},
     *     summary="Compare QR Code formats",
     *     description="Compares simple and secure QR Code formats for a ticket. Only accessible to organizers.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ticketId",
     *         in="path",
     *         required=true,
     *         description="ID of the ticket",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR Code format comparison completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="ticket_info",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="code", type="string", example="TKT123456789"),
     *                     @OA\Property(property="event", type="string", example="Summer Music Festival")
     *                 ),
     *                 @OA\Property(
     *                     property="comparison",
     *                     type="object",
     *                     @OA\Property(
     *                         property="simple_qr",
     *                         type="object",
     *                         @OA\Property(property="content", type="string", example="TKT123456789"),
     *                         @OA\Property(property="length", type="integer", example=12),
     *                         @OA\Property(property="security_level", type="string", example="BASIC"),
     *                         @OA\Property(property="anti_counterfeit", type="boolean", example=false),
     *                         @OA\Property(property="structured_data", type="boolean", example=false),
     *                         @OA\Property(property="crc_validation", type="boolean", example=false),
     *                         @OA\Property(property="format", type="string", example="PLAIN_TEXT")
     *                     ),
     *                     @OA\Property(
     *                         property="secure_qr",
     *                         type="object",
     *                         @OA\Property(property="content", type="string", example="00020101021243650016A000000025010..."),
     *                         @OA\Property(property="length", type="integer", example=150),
     *                         @OA\Property(property="security_level", type="string", example="HIGH"),
     *                         @OA\Property(property="anti_counterfeit", type="boolean", example=true),
     *                         @OA\Property(property="structured_data", type="boolean", example=true),
     *                         @OA\Property(property="crc_validation", type="boolean", example=true),
     *                         @OA\Property(property="format", type="string", example="EMVCO_AMA_COMPLIANT"),
     *                         @OA\Property(
     *                             property="security_features",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="string",
     *                                 example="unique_ticket_hash"
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="benefits_secure",
     *                         type="object",
     *                         @OA\Property(property="prevents_counterfeiting", type="string"),
     *                         @OA\Property(property="data_integrity", type="string"),
     *                         @OA\Property(property="structured_format", type="string"),
     *                         @OA\Property(property="future_proof", type="string"),
     *                         @OA\Property(property="privacy_protection", type="string"),
     *                         @OA\Property(property="audit_trail", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(property="recommendation", type="string", example="Utiliser le format sécurisé pour la production")
     *             ),
     *             @OA\Property(property="message", type="string", example="Comparaison des formats QR terminée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seuls les organisateurs peuvent comparer les formats")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Ticket]")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Comparison error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la comparaison: ...")
     *         )
     *     )
     * )
     */
    public function compareQRFormats(Request $request, $ticketId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->is_organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les organisateurs peuvent comparer les formats'
            ], 403);
        }

        $ticket = Ticket::with(['event', 'ticketType', 'buyer'])
                       ->findOrFail($ticketId);

        try {
            // QR simple (ancien format)
            $simpleQR = $ticket->code;
            
            // QR sécurisé (nouveau format EMVCO/AMA)
            $secureQR = $this->qrService->generateTicketQRCode($ticket);
            
            // Analyse comparative
            $comparison = [
                'simple_qr' => [
                    'content' => $simpleQR,
                    'length' => strlen($simpleQR),
                    'security_level' => 'BASIC',
                    'anti_counterfeit' => false,
                    'structured_data' => false,
                    'crc_validation' => false,
                    'format' => 'PLAIN_TEXT'
                ],
                'secure_qr' => [
                    'content' => $secureQR,
                    'length' => strlen($secureQR),
                    'security_level' => 'HIGH',
                    'anti_counterfeit' => true,
                    'structured_data' => true,
                    'crc_validation' => true,
                    'format' => 'EMVCO_AMA_COMPLIANT',
                    'security_features' => [
                        'unique_ticket_hash',
                        'daily_rotating_security_hash',
                        'buyer_id_obfuscation',
                        'timestamp_validation',
                        'crc16_integrity_check'
                    ]
                ],
                'benefits_secure' => [
                    'prevents_counterfeiting' => 'Hash de sécurité unique et rotatif',
                    'data_integrity' => 'CRC-16 valide l\'intégrité des données',
                    'structured_format' => 'Compatible avec les standards internationaux EMVCO',
                    'future_proof' => 'Extensible pour nouvelles fonctionnalités',
                    'privacy_protection' => 'IDs utilisateurs hashés',
                    'audit_trail' => 'Données de traçabilité intégrées'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'ticket_info' => [
                        'id' => $ticket->id,
                        'code' => $ticket->code,
                        'event' => $ticket->event->title
                    ],
                    'comparison' => $comparison,
                    'recommendation' => 'Utiliser le format sécurisé pour la production'
                ],
                'message' => 'Comparaison des formats QR terminée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la comparaison: ' . $e->getMessage()
            ], 500);
        }
    }
}