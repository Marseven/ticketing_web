<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentSuccessful;
use App\Notifications\TicketsReady;

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
     * Handle E-Billing payment webhook
     * 
     * @OA\Post(
     *     path="/api/webhooks/ebilling",
     *     operationId="handleEBillingWebhook",
     *     tags={"Webhooks"},
     *     summary="Handle E-Billing webhook",
     *     description="Processes payment status updates from E-Billing gateway",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="reference",
     *                 type="string",
     *                 description="Payment reference (external_reference from E-Billing)",
     *                 example="PAY_12345_67890"
     *             ),
     *             @OA\Property(
     *                 property="transactionid",
     *                 type="string",
     *                 description="E-Billing transaction ID",
     *                 example="TXN_EB_123456789"
     *             ),
     *             @OA\Property(
     *                 property="paymentsystem",
     *                 type="string",
     *                 description="Payment system used (AM for Airtel, MC for MobiCash)",
     *                 example="AM"
     *             ),
     *             @OA\Property(
     *                 property="amount",
     *                 type="number",
     *                 format="float",
     *                 description="Transaction amount",
     *                 example=75.50
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Webhook E-Billing traité"),
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
    public function ebilling(Request $request): JsonResponse
    {
        Log::info('Webhook E-Billing reçu', [
            'ip' => $request->ip(),
            'ip_candidates' => $this->ebillingIpCandidates($request),
            'payload' => $request->all(),
        ]);

        if (!$this->isAuthorizedEBillingRequest($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        // Valider les données du webhook E-Billing
        $reference = $request->input('reference');
        $transactionId = $request->input('transactionid');
        $paymentSystem = $request->input('paymentsystem');
        $amount = $request->input('amount');

        if (!$reference) {
            Log::error('Webhook E-Billing: référence manquante');
            return response()->json(['status' => 'error', 'message' => 'Reference missing'], 400);
        }

        // Trouver le paiement par référence (provider_txn_ref)
        $payment = Payment::where('provider_txn_ref', $reference)->first();

        if (!$payment) {
            Log::error('Webhook E-Billing: paiement non trouvé', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        Log::info('Paiement E-Billing trouvé', [
            'payment_id' => $payment->id,
            'reference' => $reference,
            'payment_system' => $paymentSystem,
            'transaction_id' => $transactionId
        ]);

        // Extraire et stocker les données E-Billing
        $ebillingData = [
            'billing_id' => $request->input('billingid'),
            'merchant_id' => $request->input('merchantid'),
            'customer_id' => $request->input('customerid'),
            'transaction_id' => $transactionId,
            'payer_id' => $request->input('payer_id'),
            'payer_code' => $request->input('payer_code'),
            'payment_system' => $paymentSystem,
            'sub_payment_system' => $request->input('subpaymentsystem'),
            'payment_system_token' => $request->input('paymentsystemtoken'),
            'payer_name' => $request->input('payername'),
            'payer_email' => $request->input('payeremail'),
            'short_description' => $request->input('shortdescription'),
            'ebilling_created_at' => $request->input('createdat') ? date('Y-m-d H:i:s', strtotime($request->input('createdat'))) : null,
            'ebilling_state' => $request->input('state'),
        ];

        $webhookData = array_merge($request->all(), [
            'received_at' => now()->toISOString(),
            'payment_system_name' => $this->getPaymentSystemFullName($paymentSystem)
        ]);

        // Recevoir une notification ne veut PAS dire que la facture est payée :
        // e-billing en émet aussi à la création de la demande et lors d'un
        // échec. On ne crédite que sur un état payé, confirmé auprès de la
        // passerelle quand la notification ne le dit pas elle-même.
        $state = $this->resolveEBillingState($request, $payment);

        if (! $this->isPaidEBillingState($state)) {
            Log::warning('Webhook E-Billing sans paiement confirmé : commande laissée en attente', [
                'payment_id' => $payment->id,
                'reference' => $reference,
                'state' => $state,
            ]);

            $this->processPaymentStatus(
                $payment,
                $this->mapEBillingState($state),
                $transactionId,
                $webhookData,
                $ebillingData
            );

            return response()->json(['status' => 'success', 'message' => 'Notification enregistrée']);
        }

        // Traiter le paiement comme réussi
        $this->processPaymentStatus($payment, 'success', $transactionId, $webhookData, $ebillingData);

        Log::info('Webhook E-Billing traité avec succès', [
            'payment_id' => $payment->id,
            'reference' => $reference
        ]);

        // Déclencher le processus de payout automatique si configuré
        try {
            $payoutService = app(\App\Services\PayoutService::class);
            $payoutService->processSuccessfulPayment($payment);
        } catch (\Exception $e) {
            Log::error('Erreur processus payout après webhook E-Billing', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Webhook E-Billing traité',
            'status' => 'ok'
        ]);
    }

    /**
     * Obtenir le nom complet du système de paiement
     */
    private function getPaymentSystemFullName(?string $paymentSystem): string
    {
        return match($paymentSystem) {
            'AM' => 'Airtel Money',
            'MM', 'MC' => 'Moov Money', // MM pour nouveau, MC pour compatibilité
            'VISA' => 'Visa Card',
            default => $paymentSystem ?? 'Inconnu'
        };
    }

    /**
     * Verify the inbound E-Billing webhook is authorized.
     *
     * Two complementary checks, both configured via config/services.php:
     *   - services.ebilling.webhook_allowed_ips: comma-separated whitelist
     *   - services.ebilling.webhook_secret: shared secret expected in the
     *     "X-Webhook-Secret" header (or "?token=" query param fallback)
     *
     * When neither is configured, the call is accepted but logged as a
     * warning so the gap is auditable. When at least one is set, every
     * configured check must pass.
     */
    private function isAuthorizedEBillingRequest(Request $request): bool
    {
        $allowedIpsCsv = (string) config('services.ebilling.webhook_allowed_ips', '');
        $secret = config('services.ebilling.webhook_secret');

        $allowedIps = array_filter(array_map('trim', explode(',', $allowedIpsCsv)));

        // Aucun contrôle configuré → ouvert (mais loggé pour audit).
        if (empty($allowedIps) && empty($secret)) {
            Log::warning('⚠️ Webhook E-Billing reçu sans contrôle d\'accès configuré', [
                'ip' => $request->ip(),
                'hint' => 'Définir EBILLING_WEBHOOK_SECRET dans .env (transmis via notification_url)',
            ]);
            return true;
        }

        // Un secret valide (en-tête X-Webhook-Secret OU query "token" porté par
        // le notification_url) suffit à autoriser, quelle que soit l'IP — les IP
        // de rappel e-billing ne sont pas fixes.
        if (!empty($secret)) {
            $provided = $request->header('X-Webhook-Secret') ?: $request->query('token');
            if (is_string($provided) && hash_equals($secret, $provided)) {
                return true;
            }
        }

        // Sinon, autoriser si une des IP de la requête (adresse distante OU
        // chaîne X-Forwarded-For, utile derrière le proxy mutualisé Hostinger)
        // figure dans la liste blanche.
        if (!empty($allowedIps) && array_intersect($this->ebillingIpCandidates($request), $allowedIps)) {
            return true;
        }

        Log::error('⛔ Webhook E-Billing rejeté', [
            'ip' => $request->ip(),
            'ip_candidates' => $this->ebillingIpCandidates($request),
            'secret_configured' => !empty($secret),
            'token_fourni' => $request->headers->has('X-Webhook-Secret') || $request->query->has('token'),
            'allowed_ips_configured' => !empty($allowedIps),
        ]);
        return false;
    }

    /**
     * IP candidates de la requête : adresse distante + chaîne X-Forwarded-For.
     * Permet de retrouver l'IP réelle d'e-billing même derrière un proxy /
     * hébergement mutualisé (où $request->ip() peut être l'IP du proxy).
     */
    private function ebillingIpCandidates(Request $request): array
    {
        $ips = $request->ips();
        $ips[] = $request->ip();
        $xff = (string) $request->header('X-Forwarded-For', '');
        foreach (explode(',', $xff) as $ip) {
            $ip = trim($ip);
            if ($ip !== '') {
                $ips[] = $ip;
            }
        }
        return array_values(array_unique(array_filter($ips)));
    }

    /**
     * Traiter le statut du paiement
     */
    private function processPaymentStatus(Payment $payment, string $status, ?string $transactionId, array $webhookData, array $ebillingData = []): void
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
            'payload' => array_merge($payment->payload ?? [], ['webhook_data' => $webhookData]),
        ];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        // Ajouter les données E-Billing si présentes
        if (!empty($ebillingData)) {
            $updateData = array_merge($updateData, array_filter($ebillingData, function($value) {
                return $value !== null && $value !== '';
            }));
        }

        if ($internalStatus === 'success') {
            // Protection contre webhooks multiples: vérifier si déjà traité
            if ($payment->status === 'success' && $payment->paid_at !== null) {
                Log::warning('🔄 Webhook déjà traité (paiement déjà marqué success)', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'paid_at' => $payment->paid_at
                ]);
                return; // Ne rien faire, déjà traité
            }

            $updateData['paid_at'] = now();

            // Marquer la commande comme payée
            $order = Order::find($payment->order_id);
            if ($order) {
                // Vérifier si la commande n'est pas déjà payée
                if ($order->status === 'paid') {
                    Log::warning('🔄 Commande déjà payée (webhook multiple détecté)', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'processed_at' => $order->processed_at
                    ]);
                    return; // Ne rien faire
                }

                $order->update([
                    'status' => 'paid',  // Utiliser 'paid' au lieu de 'completed' (enum MySQL)
                    'processed_at' => now(),
                ]);

                // Émettre les billets
                $this->issueTickets($order);

                Log::info('✅ Commande payée et billets émis', ['order_id' => $order->id]);

                // Envoyer les notifications
                try {
                    $buyer = $order->buyer;
                    if ($buyer) {
                        // Notification de paiement réussi
                        $buyer->notify(new PaymentSuccessful($payment));
                        Log::info('Notification PaymentSuccessful envoyée', ['order_id' => $order->id, 'payment_id' => $payment->id]);

                        // Notification de billets prêts
                        $buyer->notify(new TicketsReady($order));
                        Log::info('Notification TicketsReady envoyée', ['order_id' => $order->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notifications paiement', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } elseif (in_array($internalStatus, ['failed', 'cancelled', 'expired'])) {
            // Libérer les places réservées si le paiement échoue
            $order = Order::find($payment->order_id);
            if ($order) {
                // Protection contre webhooks multiples: vérifier si déjà annulé
                if ($order->status === 'cancelled') {
                    Log::warning('🔄 Commande déjà annulée (webhook multiple détecté)', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id
                    ]);
                    return; // Ne rien faire
                }

                $order->update(['status' => 'cancelled']);

                // Annuler tous les tickets de cette commande pour libérer le stock.
                // Note: l'enum tickets.status n'a pas 'cancelled' — on utilise 'void'
                // (cf. migration 2026_02_19_110434_fix_tickets_status_enum_and_issued_at).
                foreach ($order->tickets as $ticket) {
                    $ticket->update([
                        'status' => 'void',
                        'issued_at' => null
                    ]);
                }

                Log::info('✅ Commande annulée et tickets libérés', [
                    'order_id' => $order->id,
                    'tickets_count' => $order->tickets->count(),
                    'reason' => $internalStatus
                ]);
            }
        }

        $payment->update($updateData);
    }

    /**
     * États d'une facture e-billing qui valent paiement encaissé.
     * Vocabulaire repris de l'intégration MyTicketO en production.
     */
    private const EBILLING_PAID_STATES = ['processed', 'paid'];

    /**
     * État de la facture : celui annoncé par la notification, sinon celui que
     * la passerelle confirme. Retourne null si rien ne permet de conclure —
     * auquel cas on ne crédite pas.
     */
    private function resolveEBillingState(Request $request, Payment $payment): ?string
    {
        $state = $request->input('state');

        if (is_string($state) && $state !== '') {
            return strtolower(trim($state));
        }

        $billingId = $request->input('billingid') ?: $payment->billing_id;

        if (! $billingId) {
            return null;
        }

        try {
            $result = app(\App\Services\EBillingService::class)->getBillStatus((string) $billingId);
            $confirmed = $result['bill_status'] ?? null;

            Log::info('E-Billing : état de facture confirmé auprès de la passerelle', [
                'payment_id' => $payment->id,
                'billing_id' => $billingId,
                'state' => $confirmed,
            ]);

            return is_string($confirmed) ? strtolower(trim($confirmed)) : null;
        } catch (\Throwable $e) {
            // Injoignable : on préfère laisser la commande en attente plutôt
            // que de délivrer un billet non payé.
            Log::error('E-Billing : confirmation impossible', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function isPaidEBillingState(?string $state): bool
    {
        return $state !== null && in_array($state, self::EBILLING_PAID_STATES, true);
    }

    /**
     * Traduit un état e-billing non payé en statut interne.
     */
    private function mapEBillingState(?string $state): string
    {
        // Valeurs comprises par mapWebhookStatus() : tout ce qu'il ne connaît
        // pas y devient « failed », ce qui annulerait la commande à tort.
        return match ($state) {
            'failed', 'error', 'declined' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'expired' => 'expired',
            // « ready », état inconnu ou absent : la facture existe mais n'est
            // pas payée, le client peut encore régler.
            default => 'pending',
        };
    }

    /**
     * Mapper le statut du webhook vers notre statut interne
     */
    private function mapWebhookStatus(string $webhookStatus): string
    {
        return match (strtolower($webhookStatus)) {
            'success', 'completed', 'paid', 'successful' => 'success',
            'failed', 'error', 'declined' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'expired', 'timeout' => 'expired',
            'pending', 'processing' => 'initiated',
            default => 'failed',
        };
    }

    /**
     * Émettre les billets pour une commande payée
     */
    private function issueTickets(Order $order): void
    {
        // Les billets naissent ici, au paiement confirmé — pas à la commande.
        // Le service est idempotent : un webhook rejoué ne duplique rien.
        app(\App\Services\TicketIssuer::class)->issue($order);
    }

    /**
     * Handle SHAP Payout webhook
     */
    public function shapPayout(Request $request): JsonResponse
    {
        Log::info('Webhook SHAP Payout reçu', $request->all());

        try {
            $payoutService = app(\App\Services\PayoutService::class);
            $payoutService->handlePayoutCallback($request->all());

            return response()->json([
                'message' => 'Webhook SHAP Payout traité',
                'status' => 'ok'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook SHAP Payout', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Erreur lors du traitement du webhook',
                'status' => 'error'
            ], 500);
        }
    }
}