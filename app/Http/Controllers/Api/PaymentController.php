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
use App\Services\EBillingService;

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
                'gateway' => $this->getGatewayName($payment->provider),
                'date' => $payment->created_at->format('d-m-Y H:i'),
                'event_title' => $payment->order->event->title,
                'reference' => $payment->provider_txn_ref,
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
            'reference' => $payment->provider_txn_ref,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'gateway' => $this->getGatewayName($payment->provider),
            'transaction_id' => $payment->transaction_id,
            'date' => $payment->paid_at ? $payment->paid_at->format('d-m-Y H:i') : $payment->created_at->format('d-m-Y H:i'),
            'type' => 'Achat de billet',
            'event' => [
                'title' => $payment->order->event->title,
                'venue_name' => $payment->order->event->venue_name,
            ],
            'tickets_count' => $payment->order->tickets->count(),
            'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
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

        // Normaliser le provider pour correspondre à l'enum de la base
        $provider = $this->normalizeProvider($request->gateway);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider_txn_ref' => $reference,
            'provider' => $provider,
            'amount' => $order->total_amount,
            'status' => 'pending',
            'payload' => [
                'phone' => $request->phone,
                'operator' => $request->operator,
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'expired_at' => now()->addMinutes(15)->toIso8601String(), // 15 minutes pour payer
                'gateway_requested' => $request->gateway
            ]
        ]);

        // Initier le paiement selon la passerelle
        $result = $this->initiatePPayment($payment, $request);

        return response()->json($result);
    }

    /**
     * Initier le paiement selon la passerelle via E-Billing
     */
    private function initiatePPayment(Payment $payment, Request $request): array
    {
        $eBillingService = new EBillingService();
        
        // Préparer les données pour E-Billing
        $eBillingData = [
            'payer_email' => $payment->order->guest_email ?? 'customer@example.com',
            'payer_msisdn' => $eBillingService->formatPhoneNumber($request->phone ?? '074808000'),
            'amount' => (int) $payment->amount,
            'short_description' => 'Achat billet - ' . ($payment->order->tickets->first()->event->title ?? 'Event'),
            'external_reference' => $payment->provider_txn_ref,
            'payer_name' => $payment->order->guest_name ?? 'Client',
            'expiry_period' => 60, // 60 minutes
            'notification_url' => route('webhook.ebilling')
        ];

        // Logs avant l'appel API E-Billing
        \Log::info('E-Billing API Call - Create Bill', [
            'payment_id' => $payment->id,
            'data_sent' => $eBillingData,
            'provider' => $payment->provider,
        ]);

        // Créer la facture E-Billing
        $result = $eBillingService->createBill($eBillingData);

        // Logs après l'appel API E-Billing
        \Log::info('E-Billing API Response - Create Bill', [
            'payment_id' => $payment->id,
            'success' => $result['success'] ?? false,
            'result' => $result,
        ]);

        if (!$result['success']) {
            \Log::error('E-Billing API Error - Create Bill Failed', [
                'payment_id' => $payment->id,
                'error_message' => $result['message'] ?? 'Unknown error',
                'full_result' => $result,
            ]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la création de la facture'
            ];
        }

        // Mettre à jour le paiement avec l'ID de facture E-Billing
        $payment->update([
            'billing_id' => $result['bill_id'],
            'transaction_id' => $result['bill_id'],
            'payload' => array_merge($payment->payload ?? [], [
                'ebilling_bill_id' => $result['bill_id'],
                'ebilling_post_url' => $result['post_url'],
                'ebilling_created_at' => now()->toIso8601String()
            ])
        ]);

        // Selon le type de gateway, on peut soit rediriger vers E-Billing soit faire un push USSD
        switch ($payment->provider) {
            case 'airtelmoney':
            case 'moovmoney4':
                // Pour mobile money, on propose le push USSD en plus de la redirection
                return [
                    'success' => true,
                    'message' => 'Facture créée. Vous pouvez soit être redirigé vers la page de paiement, soit recevoir un push USSD.',
                    'payment_id' => $payment->id,
                    'reference' => $payment->provider_txn_ref,
                    'bill_id' => $result['bill_id'],
                    'payment_url' => $result['post_url'],
                    'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
                    'ussd_available' => true,
                    'redirect_data' => [
                        'url' => $result['post_url'],
                        'method' => 'POST',
                        'data' => [
                            'invoice_number' => $result['bill_id'],
                            'merchant_redirect_url' => route('payment.success', ['reference' => $payment->provider_txn_ref])
                        ]
                    ]
                ];

            case 'ORABANK_NG':
            case 'visa':
            case 'card':
                // Pour ORABANK_NG (Visa/Mastercard), redirection vers billing-easy.net
                $redirectUrl = "https://test.billing-easy.net?invoice={$result['bill_id']}&operator=ORABANK_NG&redirect=1";

                return [
                    'success' => true,
                    'message' => 'Redirection vers la page de paiement sécurisée',
                    'payment_id' => $payment->id,
                    'reference' => $payment->provider_txn_ref,
                    'bill_id' => $result['bill_id'],
                    'redirect_url' => $redirectUrl,
                    'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null
                ];

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
                'reference' => $payment->provider_txn_ref,
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
                    'reference' => $payment->provider_txn_ref,
                    'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
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
                'reference' => $payment->provider_txn_ref,
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
                    'reference' => $payment->provider_txn_ref,
                    'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
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
            'reference' => $payment->provider_txn_ref,
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
            'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
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
    public function getPaymentStatus($id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable'
            ], 404);
        }

        // Vérifier si le paiement a expiré
        $expiredAt = isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at']) : null;
        if ($expiredAt && $expiredAt->isPast() && $payment->status === 'initiated') {
            $payment->update(['status' => 'expired']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'reference' => $payment->provider_txn_ref,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'gateway' => $this->getGatewayName($payment->provider),
                    'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
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
        // Générer une référence unique basée sur le timestamp et un hash aléatoire
        // Note: La table payments utilise provider_txn_ref, pas reference
        return 'PAY-' . strtoupper(substr(md5(uniqid() . microtime()), 0, 10));
    }

    /**
     * Normaliser le provider pour la base de données
     */
    private function normalizeProvider(string $gateway): string
    {
        return match($gateway) {
            'moovmoney', 'moov', 'moovmoney4' => 'moovmoney4',
            'airtelmoney', 'airtel' => 'airtelmoney',
            'ORABANK_NG', 'card', 'visa' => 'ORABANK_NG',
            default => $gateway,
        };
    }

    /**
     * Obtenir le nom d'affichage de la passerelle
     */
    private function getGatewayName(?string $gateway): string
    {
        return match($gateway) {
            'airtelmoney' => 'Airtel Money',
            'moovmoney', 'moovmoney4' => 'Moov Money',
            'ORABANK_NG' => 'Visa/Mastercard',
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
            $eBillingService = new EBillingService();
            
            // Récupérer l'ID de facture E-Billing
            $billId = $payment->payload['ebilling_bill_id'] ?? $payment->transaction_id;
            
            if (!$billId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de facture E-Billing non trouvé'
                ], 400);
            }

            // Obtenir le nom du système de paiement pour E-Billing
            $paymentSystem = $eBillingService->getPaymentSystemName($request->gateway);
            
            // Formater le numéro de téléphone
            $formattedPhone = $eBillingService->formatPhoneNumber($request->phone);

            // Envoyer le push USSD via E-Billing
            $result = $eBillingService->pushUSSD($billId, $paymentSystem, $formattedPhone);

            if ($result['success']) {
                // Mettre à jour le paiement avec les infos du push
                $payment->update([
                    'payload' => array_merge($payment->payload ?? [], [
                        'push_sent_at' => now()->toISOString(),
                        'push_response' => $result['data'] ?? []
                    ])
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Push USSD envoyé avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'reference' => $payment->provider_txn_ref,
                        'bill_id' => $billId,
                        'expires_at' => isset($payment->payload['expired_at']) ? \Carbon\Carbon::parse($payment->payload['expired_at'])->format('Y-m-d H:i:s') : null,
                        'instructions' => 'Vérifiez votre téléphone et confirmez la transaction en tapant 1.',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'envoi du push USSD'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur push USSD E-Billing', [
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
            $eBillingService = new EBillingService();
            
            // Obtenir le nom du système de paiement pour E-Billing
            $paymentSystem = $eBillingService->getPaymentSystemName($request->gateway);
            
            // Formater le numéro de téléphone
            $formattedPhone = $eBillingService->formatPhoneNumber($request->phone);

            // Vérifier KYC via E-Billing
            $result = $eBillingService->checkKYC($paymentSystem, $formattedPhone);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'customer_name' => $result['customer_name'],
                        'phone' => $request->phone,
                        'gateway' => $request->gateway,
                        'account_status' => 'active'
                    ],
                    'message' => 'Informations client trouvées'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé ou compte inactif'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erreur KYC E-Billing', [
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
     * @OA\Post(
     *     path="/api/v1/payments/initiate",
     *     summary="Initiate payment for guest order",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "gateway", "amount"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="gateway", type="string", enum={"airtelmoney", "moovmoney", "ORABANK_NG"}, example="airtelmoney"),
     *             @OA\Property(property="phone", type="string", example="074123456", description="Required for mobile money"),
     *             @OA\Property(property="amount", type="number", example=15000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement initié avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="reference", type="string", example="PAY-123456")
     *                 ),
     *                 @OA\Property(property="bill_id", type="string", example="BILL-789"),
     *                 @OA\Property(property="redirect_url", type="string", example="https://test.billing-easy.net?invoice=BILL-789&operator=ORABANK_NG&redirect=1")
     *             )
     *         )
     *     )
     * )
     * 
     * Initier un paiement pour une commande invité
     */
    public function initiateGuestPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'gateway' => 'required|in:airtelmoney,moovmoney,ORABANK_NG',
            'phone' => 'required_if:gateway,airtelmoney,moovmoney|string',
            'amount' => 'required|numeric|min:100'
        ]);

        $order = Order::findOrFail($request->order_id);

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

        // Normaliser le provider pour correspondre à l'enum de la base
        $provider = $this->normalizeProvider($request->gateway);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $provider, // airtelmoney, moovmoney4, ou ORABANK_NG
            'provider_txn_ref' => $reference,
            'amount' => $request->amount,
            'status' => 'initiated',
            'payload' => [
                'phone' => $request->phone,
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'expired_at' => now()->addMinutes(15)->toIso8601String(),
                'gateway_requested' => $request->gateway // Garder trace du gateway demandé
            ]
        ]);

        // Charger les relations nécessaires pour initiatePPayment
        $payment->load('order.tickets.event');

        // Initier le paiement selon la passerelle
        $result = $this->initiatePPayment($payment, $request);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'reference' => $payment->provider_txn_ref,
                    ],
                    'bill_id' => $result['bill_id'] ?? null,
                    'redirect_url' => $result['redirect_url'] ?? null,
                ]
            ]);
        }

        return response()->json($result, 400);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/push-ussd",
     *     summary="Send USSD push for mobile money payment",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_id", "bill_id", "phone", "gateway"},
     *             @OA\Property(property="payment_id", type="integer", example=1),
     *             @OA\Property(property="bill_id", type="string", example="BILL-123"),
     *             @OA\Property(property="phone", type="string", example="074123456"),
     *             @OA\Property(property="gateway", type="string", enum={"airtelmoney", "moovmoney"}, example="airtelmoney")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="USSD push sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Push USSD envoyé avec succès")
     *         )
     *     )
     * )
     * 
     * Envoyer un push USSD
     */
    public function pushUSSD(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'phone' => 'required|string',
            'gateway' => 'required|in:airtelmoney,moovmoney',
        ]);

        $payment = Payment::findOrFail($request->payment_id);

        // Vérifier que le paiement est en attente
        if ($payment->status !== 'initiated') {
            return response()->json([
                'success' => false,
                'message' => 'Ce paiement n\'est plus en attente'
            ], 400);
        }

        try {
            $eBillingService = new EBillingService();

            // Obtenir le nom du système de paiement pour E-Billing
            $paymentSystem = $eBillingService->getPaymentSystemName($request->gateway);

            // Formater le numéro de téléphone
            $formattedPhone = $eBillingService->formatPhoneNumber($request->phone);

            $billId = $payment->billing_id;

            // Si une facture existe déjà, vérifier son statut
            if ($billId) {
                $billStatus = $eBillingService->getBillStatus($billId);

                if ($billStatus['success']) {
                    $state = $billStatus['bill_status'];

                    // Si la facture est payable (ready ou pending), on réessaie sur la même facture
                    if (in_array($state, ['ready', 'pending'])) {
                        Log::info('Réessai push sur facture existante', [
                            'payment_id' => $payment->id,
                            'bill_id' => $billId,
                            'bill_status' => $state
                        ]);

                        $result = $eBillingService->pushUSSD($billId, $paymentSystem, $formattedPhone);

                        if ($result['success']) {
                            $payment->update([
                                'payload' => array_merge($payment->payload ?? [], [
                                    'push_resent_at' => now()->toIso8601String(),
                                    'push_retry_count' => ($payment->payload['push_retry_count'] ?? 0) + 1
                                ])
                            ]);

                            return response()->json([
                                'success' => true,
                                'message' => 'Push USSD renvoyé avec succès',
                                'bill_id' => $billId
                            ]);
                        }
                    } else {
                        // Facture expirée, payée ou autre statut final → Créer nouvelle facture
                        Log::info('Création nouvelle facture - Ancienne facture non payable', [
                            'payment_id' => $payment->id,
                            'old_bill_id' => $billId,
                            'bill_status' => $state
                        ]);

                        $billId = null; // Forcer la création d'une nouvelle facture
                    }
                }
            }

            // Si pas de facture ou facture expirée, créer une nouvelle facture
            if (!$billId) {
                Log::info('Création nouvelle facture E-Billing', [
                    'payment_id' => $payment->id
                ]);

                $eBillingData = [
                    'payer_email' => $payment->order->guest_email ?? 'customer@example.com',
                    'payer_msisdn' => $formattedPhone,
                    'amount' => (int) $payment->amount,
                    'short_description' => 'Achat billet - ' . ($payment->order->tickets->first()->event->title ?? 'Event'),
                    'external_reference' => $payment->provider_txn_ref,
                    'payer_name' => $payment->order->guest_name ?? 'Client',
                    'expiry_period' => 60, // 60 minutes
                    'notification_url' => route('webhook.ebilling')
                ];

                $createResult = $eBillingService->createBill($eBillingData);

                if (!$createResult['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création de la nouvelle facture'
                    ], 400);
                }

                $billId = $createResult['bill_id'];

                // Mettre à jour le paiement avec le nouveau bill_id
                $payment->update([
                    'billing_id' => $billId,
                    'transaction_id' => $billId,
                    'payload' => array_merge($payment->payload ?? [], [
                        'ebilling_bill_id' => $billId,
                        'ebilling_post_url' => $createResult['post_url'],
                        'ebilling_recreated_at' => now()->toIso8601String()
                    ])
                ]);
            }

            // Envoyer le push USSD
            $result = $eBillingService->pushUSSD($billId, $paymentSystem, $formattedPhone);

            if ($result['success']) {
                $payment->update([
                    'payload' => array_merge($payment->payload ?? [], [
                        'push_sent_at' => now()->toIso8601String(),
                        'push_response' => $result['data'] ?? []
                    ])
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Push USSD envoyé avec succès',
                    'bill_id' => $billId
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'envoi du push USSD'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur push USSD E-Billing', [
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