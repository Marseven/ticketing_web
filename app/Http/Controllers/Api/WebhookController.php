<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="API Endpoints for payment gateway webhooks"
 * )
 */
class WebhookController extends Controller
{
    /**
     * Handle Airtel Money payment webhook
     * 
     * @OA\Post(
     *     path="/api/webhooks/airtel",
     *     operationId="handleAirtelWebhook",
     *     tags={"Webhooks"},
     *     summary="Handle Airtel Money webhook",
     *     description="Processes payment status updates from Airtel Money gateway",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reference",
     *                 type="string",
     *                 description="Payment reference from the original payment request",
     *                 example="PAY_12345_67890"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="Payment status from Airtel Money",
     *                 enum={"success", "failed", "cancelled", "pending"},
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="transaction_id",
     *                 type="string",
     *                 description="Airtel Money transaction ID",
     *                 example="TXN_AM_123456789",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="amount",
     *                 type="number",
     *                 format="float",
     *                 description="Transaction amount",
     *                 example=75.50,
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Webhook Airtel traité"),
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing reference",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Reference missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     )
     * )
     */
    public function airtel(Request $request): JsonResponse
    {
        Log::info('Webhook Airtel reçu', $request->all());

        // Valider les données du webhook Airtel
        $reference = $request->input('reference');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');
        $amount = $request->input('amount');

        if (!$reference) {
            Log::error('Webhook Airtel: référence manquante');
            return response()->json(['status' => 'error', 'message' => 'Reference missing'], 400);
        }

        // Trouver le paiement
        $payment = Payment::where('reference', $reference)
                          ->where('gateway', 'airtelmoney')
                          ->first();

        if (!$payment) {
            Log::error('Webhook Airtel: paiement non trouvé', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        // Traiter le statut
        $this->processPaymentStatus($payment, $status, $transactionId, $request->all());

        return response()->json([
            'message' => 'Webhook Airtel traité',
            'status' => 'ok'
        ]);
    }

    /**
     * Handle Moov Money payment webhook
     * 
     * @OA\Post(
     *     path="/api/webhooks/moov",
     *     operationId="handleMoovWebhook",
     *     tags={"Webhooks"},
     *     summary="Handle Moov Money webhook",
     *     description="Processes payment status updates from Moov Money gateway",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reference",
     *                 type="string",
     *                 description="Payment reference from the original payment request",
     *                 example="PAY_12345_67890"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="Payment status from Moov Money",
     *                 enum={"success", "failed", "cancelled", "pending"},
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="transaction_id",
     *                 type="string",
     *                 description="Moov Money transaction ID",
     *                 example="TXN_MOOV_123456789",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="amount",
     *                 type="number",
     *                 format="float",
     *                 description="Transaction amount",
     *                 example=75.50,
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Webhook Moov traité"),
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing reference",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Reference missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     )
     * )
     */
    public function moov(Request $request): JsonResponse
    {
        Log::info('Webhook Moov reçu', $request->all());

        // Valider les données du webhook Moov
        $reference = $request->input('reference');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');
        $amount = $request->input('amount');

        if (!$reference) {
            Log::error('Webhook Moov: référence manquante');
            return response()->json(['status' => 'error', 'message' => 'Reference missing'], 400);
        }

        // Trouver le paiement
        $payment = Payment::where('reference', $reference)
                          ->where('gateway', 'moovmoney')
                          ->first();

        if (!$payment) {
            Log::error('Webhook Moov: paiement non trouvé', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        // Traiter le statut
        $this->processPaymentStatus($payment, $status, $transactionId, $request->all());

        return response()->json([
            'message' => 'Webhook Moov traité',
            'status' => 'ok'
        ]);
    }

    /**
     * Handle Card payment webhook
     * 
     * @OA\Post(
     *     path="/api/webhooks/card",
     *     operationId="handleCardWebhook",
     *     tags={"Webhooks"},
     *     summary="Handle Card payment webhook",
     *     description="Processes payment status updates from card payment gateway",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reference",
     *                 type="string",
     *                 description="Payment reference from the original payment request",
     *                 example="PAY_12345_67890"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="Payment status from card gateway",
     *                 enum={"success", "failed", "cancelled", "pending", "declined"},
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="transaction_id",
     *                 type="string",
     *                 description="Card gateway transaction ID",
     *                 example="TXN_CARD_123456789",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="amount",
     *                 type="number",
     *                 format="float",
     *                 description="Transaction amount",
     *                 example=75.50,
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Webhook Carte traité"),
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing reference",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Reference missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Payment not found")
     *         )
     *     )
     * )
     */
    public function card(Request $request): JsonResponse
    {
        Log::info('Webhook Carte reçu', $request->all());

        // Valider les données du webhook Carte
        $reference = $request->input('reference');
        $status = $request->input('status');
        $transactionId = $request->input('transaction_id');
        $amount = $request->input('amount');

        if (!$reference) {
            Log::error('Webhook Carte: référence manquante');
            return response()->json(['status' => 'error', 'message' => 'Reference missing'], 400);
        }

        // Trouver le paiement
        $payment = Payment::where('reference', $reference)
                          ->where('gateway', 'card')
                          ->first();

        if (!$payment) {
            Log::error('Webhook Carte: paiement non trouvé', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        // Traiter le statut
        $this->processPaymentStatus($payment, $status, $transactionId, $request->all());

        return response()->json([
            'message' => 'Webhook Carte traité',
            'status' => 'ok'
        ]);
    }

    /**
     * Traiter le statut du paiement
     */
    private function processPaymentStatus(Payment $payment, string $status, ?string $transactionId, array $webhookData): void
    {
        // Mapper le statut du webhook vers notre statut interne
        $internalStatus = $this->mapWebhookStatus($status);
        
        Log::info('Traitement paiement', [
            'payment_id' => $payment->id,
            'webhook_status' => $status,
            'internal_status' => $internalStatus,
            'transaction_id' => $transactionId
        ]);

        // Mettre à jour le paiement
        $updateData = [
            'status' => $internalStatus,
            'webhook_data' => $webhookData,
        ];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        if ($internalStatus === 'successful') {
            $updateData['paid_at'] = now();
            
            // Marquer la commande comme payée
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);

                // Émettre les billets
                $this->issueTickets($order);
                
                Log::info('Commande payée et billets émis', ['order_id' => $order->id]);
            }
        } elseif (in_array($internalStatus, ['failed', 'cancelled', 'expired'])) {
            // Libérer les places réservées si le paiement échoue
            $order = Order::find($payment->order_id);
            if ($order) {
                $order->update(['status' => 'failed']);
                // TODO: Libérer les quantités réservées dans les TicketTypes
            }
        }

        $payment->update($updateData);
    }

    /**
     * Mapper le statut du webhook vers notre statut interne
     */
    private function mapWebhookStatus(string $webhookStatus): string
    {
        return match (strtolower($webhookStatus)) {
            'success', 'completed', 'paid', 'successful' => 'successful',
            'failed', 'error', 'declined' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'expired', 'timeout' => 'expired',
            'pending', 'processing' => 'pending',
            default => 'failed',
        };
    }

    /**
     * Émettre les billets pour une commande payée
     */
    private function issueTickets(Order $order): void
    {
        // Générer les codes QR uniques pour chaque billet
        foreach ($order->tickets as $ticket) {
            if (empty($ticket->code)) {
                $ticket->generateQRCode();
            }
            
            $ticket->update([
                'status' => 'issued',
                'issued_at' => now(),
            ]);
        }

        // TODO: Envoyer les billets par email/SMS
        Log::info('Billets émis pour la commande', [
            'order_id' => $order->id,
            'tickets_count' => $order->tickets->count()
        ]);
    }
}