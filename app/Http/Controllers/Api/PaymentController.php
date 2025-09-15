<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payments",
     *     summary="Get user's payment list",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\Response(
     *         response=200,
     *         description="Payment list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payments", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="amount", type="number", example=15000),
     *                         @OA\Property(property="status", type="string", example="Payé"),
     *                         @OA\Property(property="gateway", type="string", example="Airtel Money"),
     *                         @OA\Property(property="date", type="string", example="12-09-2025 14:30"),
     *                         @OA\Property(property="event_title", type="string", example="Concert de Jazz"),
     *                         @OA\Property(property="reference", type="string", example="PAY-A1B2C3D4E5"),
     *                         @OA\Property(property="type", type="string", example="Achat de billet")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Liste des paiements")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * Liste des paiements de l'utilisateur
     */
    public function payments(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $payments = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('buyer_id', $user->id);
        })
        ->with(['order.event'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => round($payment->amount),
                'status' => $this->getStatusMessage($payment->status),
                'gateway' => $this->getGatewayName($payment->gateway),
                'date' => $payment->created_at->format('d-m-Y H:i'),
                'event_title' => $payment->order->event->title,
                'reference' => $payment->reference,
                'type' => 'Achat de billet',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['payments' => $payments],
            'message' => 'Liste des paiements'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/payments/{id}",
     *     summary="Get payment details",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="reference", type="string", example="PAY-A1B2C3D4E5"),
     *                     @OA\Property(property="amount", type="number", example=15000),
     *                     @OA\Property(property="status", type="string", example="successful"),
     *                     @OA\Property(property="gateway", type="string", example="Airtel Money"),
     *                     @OA\Property(property="transaction_id", type="string", example="TXN123456789"),
     *                     @OA\Property(property="date", type="string", example="12-09-2025 14:30"),
     *                     @OA\Property(property="type", type="string", example="Achat de billet"),
     *                     @OA\Property(property="event", type="object",
     *                         @OA\Property(property="title", type="string", example="Concert de Jazz"),
     *                         @OA\Property(property="venue_name", type="string", example="Palais des Congrès")
     *                     ),
     *                     @OA\Property(property="tickets_count", type="integer", example=2),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-12T15:45:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Paiement n° 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paiement non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * Détails d'un paiement
     */
    public function payment(Request $request, $id): JsonResponse
    {
        $payment = Payment::with(['order.event', 'order.tickets'])
            ->findOrFail($id);

        // Vérifier que l'utilisateur a accès à ce paiement
        if ($payment->order->buyer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé'
            ], 404);
        }

        $paymentData = [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'gateway' => $this->getGatewayName($payment->gateway),
            'transaction_id' => $payment->transaction_id,
            'date' => $payment->paid_at ? $payment->paid_at->format('d-m-Y H:i') : $payment->created_at->format('d-m-Y H:i'),
            'type' => 'Achat de billet',
            'event' => [
                'title' => $payment->order->event->title,
                'venue_name' => $payment->order->event->venue_name,
            ],
            'tickets_count' => $payment->order->tickets->count(),
            'expires_at' => $payment->expired_at,
        ];

        return response()->json([
            'success' => true,
            'data' => ['payment' => $paymentData],
            'message' => 'Paiement n° ' . $payment->id
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{orderId}/payment",
     *     summary="Process payment for an order",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID to process payment for",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"gateway"},
     *             @OA\Property(property="gateway", type="string", enum={"airtelmoney", "moovmoney", "card"}, example="airtelmoney"),
     *             @OA\Property(property="phone", type="string", pattern="^(074|077|076|060|062|066|065)\\d{6}$", example="0741234567", description="Required for mobile money payments. Must be Airtel (074,077,076) or Moov (060,062,066,065) number"),
     *             @OA\Property(property="operator", type="string", example="airtel", description="Mobile operator (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     description="Mobile Money Payment Response",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Push USSD envoyé sur votre mobile. Veuillez valider la transaction."),
     *                     @OA\Property(property="payment_id", type="integer", example=1),
     *                     @OA\Property(property="reference", type="string", example="PAY-A1B2C3D4E5"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-12T15:45:00"),
     *                     @OA\Property(property="push_sent", type="boolean", example=true),
     *                     @OA\Property(property="instructions", type="string", example="Vous allez recevoir une demande de confirmation sur votre téléphone. Tapez 1 pour confirmer.")
     *                 ),
     *                 @OA\Schema(
     *                     description="Card Payment Response",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Redirection vers la page de paiement"),
     *                     @OA\Property(property="payment_id", type="integer", example=1),
     *                     @OA\Property(property="payment_url", type="string", example="https://secure.payment.com?reference=PAY-A1B2C3D4E5"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-12T15:45:00")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Order expired or invalid",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cette commande a expiré")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Commande non trouvée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * Traiter un paiement pour une commande
     */
    public function processPayment(PaymentRequest $request, $orderId): JsonResponse
    {

        $order = Order::findOrFail($orderId);
        
        // Vérifier que l'utilisateur a accès à cette commande
        if ($order->buyer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        // Vérifier que la commande n'est pas expirée
        if ($order->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande a expiré'
            ], 400);
        }

        // Vérifier si un paiement en cours existe déjà
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPayment) {
            return $this->getPaymentStatus($existingPayment);
        }

        // Créer un nouveau paiement
        $reference = $this->generateReference();
        
        $payment = Payment::create([
            'order_id' => $order->id,
            'reference' => $reference,
            'gateway' => $request->gateway,
            'amount' => $order->total_amount,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(15), // 15 minutes pour payer
            'metadata' => [
                'phone' => $request->phone,
                'operator' => $request->operator,
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
            ]
        ]);

        // Initier le paiement selon la passerelle
        $result = $this->initiatePPayment($payment, $request);

        return response()->json($result);
    }

    /**
     * Initier le paiement selon la passerelle
     */
    private function initiatePPayment(Payment $payment, Request $request): array
    {
        switch ($payment->gateway) {
            case 'airtelmoney':
                return $this->initiateAirtelPayment($payment, $request->phone);
            
            case 'moovmoney':
                return $this->initiateMoovPayment($payment, $request->phone);
            
            case 'card':
                return $this->initiateCardPayment($payment);
            
            default:
                return [
                    'success' => false,
                    'message' => 'Passerelle de paiement non supportée'
                ];
        }
    }

    /**
     * Initier un paiement Airtel Money
     */
    private function initiateAirtelPayment(Payment $payment, string $phone): array
    {
        try {
            // Simulation d'appel API Airtel Money
            $response = Http::timeout(30)->post(env('AIRTEL_API_URL', 'https://api.airtel.com/payment'), [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'phone' => $phone,
                'description' => 'Achat billet - ' . $payment->order->event->title,
                'callback_url' => route('webhook.airtel'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $payment->update([
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'gateway_response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Push USSD envoyé sur votre mobile. Veuillez valider la transaction.',
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'expires_at' => $payment->expired_at->format('Y-m-d H:i:s'),
                    'push_sent' => true,
                    'instructions' => 'Vous allez recevoir une demande de confirmation sur votre téléphone. Tapez 1 pour confirmer.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement Airtel Money'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur paiement Airtel: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur de connexion avec Airtel Money'
            ];
        }
    }

    /**
     * Initier un paiement Moov Money
     */
    private function initiateMoovPayment(Payment $payment, string $phone): array
    {
        try {
            // Simulation d'appel API Moov Money
            $response = Http::timeout(30)->post(env('MOOV_API_URL', 'https://api.moov.com/payment'), [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'phone' => $phone,
                'description' => 'Achat billet - ' . $payment->order->event->title,
                'callback_url' => route('webhook.moov'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $payment->update([
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'gateway_response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Push USSD envoyé sur votre mobile. Veuillez valider la transaction.',
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'expires_at' => $payment->expired_at->format('Y-m-d H:i:s'),
                    'push_sent' => true,
                    'instructions' => 'Vous allez recevoir une demande de confirmation sur votre téléphone. Tapez 1 pour confirmer.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initialisation du paiement Moov Money'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur paiement Moov: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur de connexion avec Moov Money'
            ];
        }
    }

    /**
     * Initier un paiement par carte
     */
    private function initiateCardPayment(Payment $payment): array
    {
        // Générer une URL de paiement sécurisée
        $paymentUrl = env('CARD_PAYMENT_URL', 'https://secure.payment.com') . '?' . http_build_query([
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => 'XAF', // Franc CFA CEMAC (Gabon)
            'return_url' => env('APP_URL') . '/payment/success',
            'cancel_url' => env('APP_URL') . '/payment/cancel',
            'callback_url' => route('webhook.card'),
        ]);

        return [
            'success' => true,
            'message' => 'Redirection vers la page de paiement',
            'payment_id' => $payment->id,
            'payment_url' => $paymentUrl,
            'expires_at' => $payment->expired_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/payments/{payment}/status",
     *     summary="Get payment status",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="payment",
     *         in="path",
     *         required=true,
     *         description="Payment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="reference", type="string", example="PAY-A1B2C3D4E5"),
     *                     @OA\Property(property="status", type="string", enum={"pending", "successful", "failed", "expired", "cancelled"}, example="successful"),
     *                     @OA\Property(property="amount", type="number", example=15000),
     *                     @OA\Property(property="gateway", type="string", example="Airtel Money"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-12T15:45:00"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", example="2025-09-12T14:35:00")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Statut du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     )
     * )
     * 
     * Vérifier le statut d'un paiement
     */
    public function getPaymentStatus(Payment $payment): JsonResponse
    {
        // Vérifier si le paiement a expiré
        if ($payment->expired_at && $payment->expired_at->isPast() && $payment->status === 'pending') {
            $payment->update(['status' => 'expired']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'gateway' => $this->getGatewayName($payment->gateway),
                    'expires_at' => $payment->expired_at?->format('Y-m-d H:i:s'),
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                ]
            ],
            'message' => 'Statut du paiement'
        ]);
    }

    /**
     * Générer une référence de paiement unique
     */
    private function generateReference(): string
    {
        do {
            $reference = 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 10));
        } while (Payment::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Obtenir le nom d'affichage de la passerelle
     */
    private function getGatewayName(?string $gateway): string
    {
        return match($gateway) {
            'airtelmoney' => 'Airtel Money',
            'moovmoney' => 'Moov Money',
            'card' => 'Carte bancaire',
            default => 'Inconnu',
        };
    }

    /**
     * @OA\Post(
     *     path="/api/payments/push-ussd",
     *     summary="Send USSD push for mobile money payment",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_id", "phone", "gateway"},
     *             @OA\Property(property="payment_id", type="integer", example=1, description="Payment ID to send push for"),
     *             @OA\Property(property="phone", type="string", example="0741234567", description="Phone number to send USSD push"),
     *             @OA\Property(property="gateway", type="string", enum={"airtelmoney", "moovmoney"}, example="airtelmoney", description="Mobile money gateway")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="USSD push sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Push USSD envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_id", type="integer", example=1),
     *                 @OA\Property(property="reference", type="string", example="PAY-A1B2C3D4E5"),
     *                 @OA\Property(property="transaction_id", type="string", example="TXN123456789"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-09-12T15:45:00"),
     *                 @OA\Property(property="instructions", type="string", example="Vérifiez votre téléphone et confirmez la transaction en tapant 1.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Payment not pending or USSD push failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce paiement n'est plus en attente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès refusé à ce paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur technique lors de l'envoi du push USSD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * Envoyer un push USSD au client (similaire à pushUssd)
     */
    public function sendPushUSSD(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|integer',
            'phone' => 'required|string',
            'gateway' => 'required|in:airtelmoney,moovmoney',
        ]);

        $payment = Payment::findOrFail($request->payment_id);
        
        // Vérifier que l'utilisateur a accès à ce paiement
        if ($payment->order->buyer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé à ce paiement'
            ], 403);
        }

        // Vérifier que le paiement est en attente
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement n\'est plus en attente'
            ], 400);
        }

        try {
            // Authentification selon la passerelle
            $auth = $request->gateway === 'airtelmoney' 
                ? env('AIRTEL_USERNAME') . ':' . env('AIRTEL_SECRET')
                : env('MOOV_USERNAME') . ':' . env('MOOV_SECRET');
            
            $base64 = base64_encode($auth);

            // URL API selon la passerelle
            $apiUrl = $request->gateway === 'airtelmoney' 
                ? env('AIRTEL_PUSH_URL', 'https://api.airtel.ga/ussd_push')
                : env('MOOV_PUSH_URL', 'https://api.moov.ga/ussd_push');

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $base64,
                'Content-Type' => 'application/json'
            ])->post($apiUrl, [
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'phone' => $request->phone,
                'currency' => 'XAF',
                'description' => 'Achat billet - ' . $payment->order->event->title,
                'callback_url' => $request->gateway === 'airtelmoney' 
                    ? route('webhook.airtel') 
                    : route('webhook.moov'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Mettre à jour le paiement avec les infos de la transaction
                $payment->update([
                    'transaction_id' => $data['transaction_id'] ?? $data['reference'] ?? null,
                    'gateway_response' => $data,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'push_sent_at' => now()->toISOString(),
                        'push_response' => $data
                    ])
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Push USSD envoyé avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'reference' => $payment->reference,
                        'transaction_id' => $data['transaction_id'] ?? null,
                        'expires_at' => $payment->expired_at->format('Y-m-d H:i:s'),
                        'instructions' => 'Vérifiez votre téléphone et confirmez la transaction en tapant 1.',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du push USSD: ' . $response->body()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur push USSD', [
                'payment_id' => $payment->id,
                'gateway' => $request->gateway,
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de l\'envoi du push USSD'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payments/kyc",
     *     summary="Check KYC (Know Your Customer) information",
     *     tags={"Payments"},
     *     security={"bearerAuth": {}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "gateway"},
     *             @OA\Property(property="phone", type="string", example="0741234567", description="Phone number to check KYC for"),
     *             @OA\Property(property="gateway", type="string", enum={"airtelmoney", "moovmoney"}, example="airtelmoney", description="Mobile money gateway")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer information found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="0741234567"),
     *                 @OA\Property(property="gateway", type="string", example="airtelmoney"),
     *                 @OA\Property(property="account_status", type="string", example="active")
     *             ),
     *             @OA\Property(property="message", type="string", example="Informations client trouvées")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found or account inactive",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client non trouvé ou compte inactif")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la vérification KYC")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * 
     * Vérification KYC (Know Your Customer) - similaire à votre exemple
     */
    public function checkKYC(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'gateway' => 'required|in:airtelmoney,moovmoney',
        ]);

        try {
            $auth = $request->gateway === 'airtelmoney' 
                ? env('AIRTEL_USERNAME') . ':' . env('AIRTEL_SECRET')
                : env('MOOV_USERNAME') . ':' . env('MOOV_SECRET');
            
            $base64 = base64_encode($auth);

            $apiUrl = $request->gateway === 'airtelmoney' 
                ? env('AIRTEL_KYC_URL', 'https://api.airtel.ga/kyc')
                : env('MOOV_KYC_URL', 'https://api.moov.ga/kyc');

            $url = $apiUrl . '?' . http_build_query([
                'operator' => $request->gateway,
                'phone' => $request->phone
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $base64
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['customer_name'])) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'customer_name' => $data['customer_name'],
                            'phone' => $request->phone,
                            'gateway' => $request->gateway,
                            'account_status' => $data['status'] ?? 'active'
                        ],
                        'message' => 'Informations client trouvées'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé ou compte inactif'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur KYC', [
                'gateway' => $request->gateway,
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification KYC'
            ], 500);
        }
    }

    /**
     * Obtenir le message de statut
     */
    private function getStatusMessage(string $status): string
    {
        return match($status) {
            'pending' => 'En attente',
            'successful' => 'Payé',
            'failed' => 'Échoué',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }
}