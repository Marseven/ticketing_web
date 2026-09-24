<?php

namespace App\Http\Controllers\Api;

use App\Support\Redact;
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
        Log::info('Webhook Airtel reçu', Redact::payload($request->all()));

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
        Log::info('Webhook Moov reçu', Redact::payload($request->all()));

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
        Log::info('Webhook Carte reçu', Redact::payload($request->all()));

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
        // `jeton_fourni` règle une question qu'on ne peut pas trancher depuis
        // le code : nous plaçons bien un jeton dans l'URL de notification à la
        // création de la facture, mais rien ne dit qu'e-billing le renvoie sur
        // le rappel automatique qui suit la confirmation de l'opérateur. Tant
        // qu'on l'ignore, exiger ce jeton reviendrait à parier sur le compte
        // des clients. Le journal répond au bout de quelques paiements réels.
        Log::info('Webhook E-Billing reçu', [
            'ip' => $request->ip(),
            'ip_candidates' => $this->ebillingIpCandidates($request),
            'jeton_fourni' => $request->headers->has('X-Webhook-Secret') || $request->query->has('token'),
            'payload' => Redact::payload($request->all()),
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
     * Le rappel de versement vient-il bien de SHAP ?
     *
     * Ce point d'entrée fait SORTIR de l'argent : un appel « échec » recrédite
     * le solde de l'organisateur. Laissé ouvert, il permettait à quiconque
     * connaissant une `external_reference` — un organisateur la lit dans son
     * propre espace — de gonfler un solde à volonté puis de le retirer.
     *
     * Il est donc REFUSÉ PAR DÉFAUT, contrairement au rappel d'encaissement :
     * aucune configuration ne vaut aucun accès. C'est sans risque, car l'URL de
     * rappel n'est jamais transmise à SHAP et la réconciliation réelle passe
     * par `payout:check-status`, exécuté toutes les cinq minutes.
     */
    private function isAuthorizedShapRequest(Request $request): bool
    {
        $secret = (string) config('services.shap.webhook_secret', '');

        if ($secret === '') {
            Log::warning('⛔ Rappel SHAP refusé : aucun secret configuré', [
                'ip' => $request->ip(),
                'hint' => 'Définir SHAP_WEBHOOK_SECRET dans .env pour autoriser ce rappel.',
            ]);

            return false;
        }

        $provided = $request->header('X-Webhook-Secret') ?: $request->query('token');

        if (is_string($provided) && hash_equals($secret, $provided)) {
            return true;
        }

        Log::error('⛔ Rappel SHAP refusé : secret absent ou invalide', [
            'ip' => $request->ip(),
            'token_fourni' => $request->headers->has('X-Webhook-Secret') || $request->query->has('token'),
        ]);

        return false;
    }

    /**
     * Le rappel d'encaissement vient-il bien d'e-billing ?
     *
     * **L'adresse est le seul contrôle possible.** E-billing n'accepte aucun
     * paramètre dans l'URL de notification : le jeton qu'on y plaçait ne
     * revenait jamais sur le rappel automatique qui suit la confirmation de
     * l'opérateur. L'exiger aurait bloqué toutes les confirmations de paiement
     * — clients débités, billets non émis. Le contrôle repose donc entièrement
     * sur `services.ebilling.webhook_allowed_ips`.
     *
     * Deux défauts corrigés au passage.
     *
     * 1. ⚠️ Il s'OUVRAIT quand rien n'était configuré : l'absence de réglage
     *    valait autorisation. Sur une installation où la variable manque — un
     *    nouveau serveur, un `.env` recopié à la hâte — n'importe qui pouvait
     *    déclarer un paiement réglé et se faire émettre des billets.
     *
     * 2. ⚠️ Il lisait l'en-tête « X-Forwarded-For » BRUT pour fabriquer ses
     *    adresses candidates. Cet en-tête est écrit par l'appelant : il
     *    suffisait d'y placer une adresse autorisée. Voir
     *    `ebillingIpCandidates()`, qui ne retient plus que des valeurs
     *    qu'un appelant ne peut pas choisir.
     */
    private function isAuthorizedEBillingRequest(Request $request): bool
    {
        $allowedIps = array_filter(array_map(
            'trim',
            explode(',', (string) config('services.ebilling.webhook_allowed_ips', ''))
        ));

        // Aucune adresse déclarée → refus. Rien ne permettrait alors d'attester
        // l'origine, et accepter reviendrait à distribuer des billets à qui
        // connaît l'URL. Le coût d'un refus est borné :
        // `payments:check-pending` interroge la passerelle toutes les cinq
        // minutes et rattrape la confirmation.
        if (empty($allowedIps)) {
            Log::error('⛔ Webhook E-Billing refusé : aucune adresse autorisée configurée', [
                'adresses_constatees' => $this->ebillingIpCandidates($request),
                'hint' => 'Renseigner EBILLING_WEBHOOK_ALLOWED_IPS avec les adresses ci-dessus.',
            ]);

            return false;
        }

        if (array_intersect($this->ebillingIpCandidates($request), $allowedIps)) {
            return true;
        }

        Log::error('⛔ Webhook E-Billing refusé : adresse hors liste', [
            'adresses_constatees' => $this->ebillingIpCandidates($request),
        ]);

        return false;
    }

    /**
     * Les adresses de la requête retenues pour la liste blanche.
     *
     * ⚠️ Cette méthode ajoutait auparavant les valeurs BRUTES de l'en-tête
     * « X-Forwarded-For ». Cet en-tête est écrit par l'appelant : il suffisait
     * d'y placer une adresse autorisée pour franchir le contrôle. On s'en
     * remet désormais à `$request->ips()`, que le cadre calcule en fonction des
     * proxies déclarés de confiance.
     *
     * @return array<int, string>
     */
    private function ebillingIpCandidates(Request $request): array
    {
        // `REMOTE_ADDR` est le pair TCP : infalsifiable. La DERNIÈRE entrée de
        // « X-Forwarded-For » est celle qu'ajoute le proxy le plus proche, en
        // notant de qui il a reçu la requête.
        //
        // Un appelant peut insérer des entrées dans cet en-tête, mais elles se
        // placent AVANT celle du proxy : forger « X-Forwarded-For: 41.158.0.1 »
        // produit « 41.158.0.1, <son adresse réelle> », et c'est la seconde
        // qu'on lit. Les valeurs du milieu, entièrement sous son contrôle, sont
        // ignorées.
        //
        // ⚠️ Ne jamais réintroduire ici `$request->ips()` ni `$request->ip()` :
        // avec `trustProxies(at: '*')`, ils rendent l'en-tête tel que
        // l'appelant l'a écrit.
        $remote = (string) $request->server('REMOTE_ADDR', '');
        $candidates = [$remote];

        // Et l'en-tête n'est consulté QUE si la requête nous arrive d'un proxy
        // local — boucle ou réseau privé. Une requête venue d'Internet a un
        // `REMOTE_ADDR` public : si celui-ci n'est pas dans la liste, aucun
        // en-tête ne doit pouvoir la rattraper. Sans cette condition, il
        // suffisait d'écrire soi-même une adresse autorisée pour entrer.
        if ($remote !== '' && $this->isLocalProxy($remote)) {
            $forwarded = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) $request->header('X-Forwarded-For', ''))
            )));

            if ($forwarded !== []) {
                $candidates[] = end($forwarded);
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * La requête nous arrive-t-elle d'un intermédiaire de l'hébergeur ?
     *
     * Seule une adresse de boucle ou de réseau privé peut l'être : un appelant
     * depuis Internet ne peut pas se présenter avec une telle adresse au
     * niveau TCP.
     */
    private function isLocalProxy(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
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

            // Encaissement : même chemin que la vérification périodique des
            // paiements en attente (payments:check-pending), pour qu'un billet
            // émis sur notification soit identique à un billet émis sur relance.
            // Le service est idempotent : un webhook rejoué ne crédite pas deux fois.
            app(\App\Services\PaymentConfirmation::class)->confirm($payment, $updateData);

            return;
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
     * Vocabulaire repris de l'ancienne intégration en production.
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
            // « unpaid » et « ready » : la facture existe et n'est pas réglée.
            // C'est l'état qu'e-billing a renvoyé sur les commandes créditées à
            // tort en septembre 2026. Le client peut encore payer, donc la
            // commande reste en attente plutôt qu'annulée.
            'unpaid', 'ready', 'pending' => 'pending',
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
        if (! $this->isAuthorizedShapRequest($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        Log::info('Webhook SHAP Payout reçu', Redact::payload($request->all()));

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