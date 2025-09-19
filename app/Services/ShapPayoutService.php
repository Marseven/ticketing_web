<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShapPayoutService
{
    private string $apiId;
    private string $apiSecret;
    private string $baseUrl;
    private ?string $accessToken = null;
    private ?Carbon $tokenExpiresAt = null;

    public function __construct()
    {
        $this->apiId = env('API_PAYOUT_ID');
        $this->apiSecret = env('API_PAYOUT_SECRET');
        $this->baseUrl = env('SHAP_BASE_URL', 'https://test.billing-easy.net/shap/api/v1/merchant/');
    }

    /**
     * Obtenir un token d'accès OAuth 2.0
     */
    private function getAccessToken(): string
    {
        // Vérifier si le token actuel est encore valide
        if ($this->accessToken && $this->tokenExpiresAt && $this->tokenExpiresAt->isFuture()) {
            return $this->accessToken;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . 'auth', [
                'api_id' => $this->apiId,
                'api_secret' => $this->apiSecret
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->accessToken = $data['access_token'];
                $this->tokenExpiresAt = now()->addSeconds($data['expires_in'] - 60); // 60 secondes de marge
                
                Log::info('Token SHAP obtenu avec succès', [
                    'expires_in' => $data['expires_in'],
                    'expires_at' => $this->tokenExpiresAt->toISOString()
                ]);
                
                return $this->accessToken;
            }

            $errorData = $response->json();
            Log::error('Erreur obtention token SHAP', [
                'status' => $response->status(),
                'error' => $errorData
            ]);

            throw new \Exception($errorData['error_description'] ?? 'Erreur lors de l\'obtention du token');

        } catch (\Exception $e) {
            Log::error('Exception obtention token SHAP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un payout
     * 
     * @param string $paymentSystemName (airtelmoney, moovmoney4, etc.)
     * @param string $payeeMsisdn Numéro de téléphone du bénéficiaire
     * @param float $amount Montant à envoyer
     * @param string $externalReference Référence externe unique
     * @param string $payoutType (refund, cashback, withdrawal)
     */
    public function createPayout(
        string $paymentSystemName,
        string $payeeMsisdn,
        float $amount,
        string $externalReference,
        string $payoutType = 'withdrawal'
    ): array {
        try {
            // 1. Vérifier le solde disponible avant de créer le payout
            $balanceCheck = $this->checkBalance($paymentSystemName, $amount);
            if (!$balanceCheck['success']) {
                return $balanceCheck;
            }

            $token = $this->getAccessToken();

            $payoutData = [
                'payee_msisdn' => $this->formatPhoneNumber($payeeMsisdn),
                'amount' => $amount,
                'external_reference' => $externalReference,
                'payout_type' => $payoutType
            ];

            Log::info('Création payout SHAP', [
                'payment_system' => $paymentSystemName,
                'payout_data' => $payoutData
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->post($this->baseUrl . 'payout', [
                'payment_system_name' => $paymentSystemName,
                'payout' => json_encode($payoutData)
            ]);

            $responseData = $response->json();

            Log::info('Réponse payout SHAP', [
                'status' => $response->status(),
                'data' => $responseData
            ]);

            if ($response->successful() && isset($responseData['successful'])) {
                // Vérifier si c'est un statut ambigus qui nécessite un check
                $ambiguousStatuses = ['transaction_initiated', 'processing', 'pending'];
                $isAmbiguous = in_array($responseData['successful'], $ambiguousStatuses);
                
                return [
                    'success' => true,
                    'data' => $responseData['response'],
                    'message' => $responseData['success_message'] ?? 'Payout créé avec succès',
                    'is_synchronous' => !$isAmbiguous,
                    'requires_status_check' => $isAmbiguous,
                    'shap_status' => $responseData['successful']
                ];
            }

            return [
                'success' => false,
                'error_code' => $responseData['error_code'] ?? 'UNKNOWN',
                'message' => $responseData['error_description'] ?? 'Erreur lors de la création du payout',
                'requires_status_check' => false
            ];

        } catch (\Exception $e) {
            Log::error('Exception création payout SHAP', [
                'error' => $e->getMessage(),
                'payment_system' => $paymentSystemName,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la création du payout',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le solde par opérateur
     */
    public function getBalance(): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->get($this->baseUrl . 'balance');

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'data' => $data['data']
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'message' => $errorData['error_description'] ?? 'Erreur lors de la récupération du solde'
            ];

        } catch (\Exception $e) {
            Log::error('Exception récupération solde SHAP', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la récupération du solde'
            ];
        }
    }

    /**
     * Obtenir les détails d'un payout pour vérifier son statut
     */
    public function getPayout(string $payeeMsisdn, string $externalReference): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ])->get($this->baseUrl . 'payout', [
                'payee_msisdn' => $this->formatPhoneNumber($payeeMsisdn),
                'external_reference' => $externalReference
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Statut payout SHAP récupéré', [
                    'external_reference' => $externalReference,
                    'payout_data' => $data['payout']
                ]);
                
                return [
                    'success' => true,
                    'data' => $data['payout'],
                    'status' => $data['payout']['state'] ?? 'unknown'
                ];
            }

            $errorData = $response->json();
            Log::warning('Payout SHAP non trouvé', [
                'external_reference' => $externalReference,
                'error' => $errorData
            ]);
            
            return [
                'success' => false,
                'message' => $errorData['error_description'] ?? 'Payout non trouvé',
                'error_code' => $errorData['error_code'] ?? 'SP0027'
            ];

        } catch (\Exception $e) {
            Log::error('Exception récupération payout SHAP', [
                'error' => $e->getMessage(),
                'payee_msisdn' => $payeeMsisdn,
                'external_reference' => $externalReference
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la récupération du payout'
            ];
        }
    }

    /**
     * Vérifier le statut d'un payout et retourner un statut normalisé
     */
    public function checkPayoutStatus(string $payeeMsisdn, string $externalReference): array
    {
        $result = $this->getPayout($payeeMsisdn, $externalReference);
        
        if (!$result['success']) {
            return $result;
        }

        $shapStatus = $result['status'];
        $normalizedStatus = $this->normalizePayoutStatus($shapStatus);
        
        return [
            'success' => true,
            'shap_status' => $shapStatus,
            'normalized_status' => $normalizedStatus,
            'data' => $result['data'],
            'is_final' => in_array($normalizedStatus, ['success', 'failed'])
        ];
    }

    /**
     * Normaliser le statut SHAP vers nos statuts internes
     */
    private function normalizePayoutStatus(string $shapStatus): string
    {
        return match(strtolower($shapStatus)) {
            'success', 'completed', 'paid' => 'success',
            'failed', 'error', 'declined', 'rejected' => 'failed',
            'pending', 'initiated', 'processing' => 'processing',
            'cancelled', 'canceled' => 'cancelled',
            default => 'processing', // Par défaut, on considère comme en cours
        };
    }

    /**
     * Mapper le gateway vers le payment_system_name SHAP
     */
    public function getPaymentSystemName(string $gateway): string
    {
        return match($gateway) {
            'airtelmoney' => 'airtelmoney',
            'moovmoney' => 'moovmoney4', // Moov Money version 4 selon la doc
            default => $gateway
        };
    }

    /**
     * Formater le numéro de téléphone pour SHAP
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Retirer tous les espaces et caractères non numériques
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro commence par +241, retirer le +241
        if (strpos($phone, '241') === 0) {
            $phone = substr($phone, 3);
        }
        
        // S'assurer que le numéro fait 9 chiffres
        if (strlen($phone) === 9) {
            return $phone;
        }
        
        return $phone;
    }

    /**
     * Vérifier le solde PAYIN disponible pour un opérateur avant un payout
     */
    public function checkBalance(string $paymentSystemName, float $amount): array
    {
        try {
            $balanceResult = $this->getBalance();
            
            if (!$balanceResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Impossible de vérifier le solde SHAP'
                ];
            }

            // Chercher le solde PAYIN pour l'opérateur spécifique
            $operatorBalance = null;
            foreach ($balanceResult['data'] as $balance) {
                if ($balance['payment_system_name'] === $paymentSystemName) {
                    $operatorBalance = $balance;
                    break;
                }
            }

            if (!$operatorBalance) {
                return [
                    'success' => false,
                    'message' => "Aucun solde PAYIN trouvé pour l'opérateur {$paymentSystemName}"
                ];
            }

            // Le solde retourné par l'API balance correspond au solde PAYIN (encaissements)
            $availablePayinAmount = $operatorBalance['amount'];
            
            Log::info('Vérification solde PAYIN SHAP', [
                'payment_system' => $paymentSystemName,
                'available_payin_amount' => $availablePayinAmount,
                'requested_payout_amount' => $amount,
                'sufficient' => $availablePayinAmount >= $amount
            ]);

            if ($availablePayinAmount < $amount) {
                return [
                    'success' => false,
                    'error_code' => 'SP0016',
                    'message' => "Solde PAYIN insuffisant. Disponible: {$availablePayinAmount} XAF, Payout demandé: {$amount} XAF",
                    'available_payin_balance' => $availablePayinAmount,
                    'requested_payout_amount' => $amount,
                    'balance_type' => 'PAYIN'
                ];
            }

            return [
                'success' => true,
                'available_payin_balance' => $availablePayinAmount,
                'requested_payout_amount' => $amount,
                'balance_type' => 'PAYIN'
            ];

        } catch (\Exception $e) {
            Log::error('Exception vérification solde PAYIN SHAP', [
                'error' => $e->getMessage(),
                'payment_system' => $paymentSystemName,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la vérification du solde PAYIN'
            ];
        }
    }

    /**
     * Obtenir le solde disponible pour un opérateur spécifique
     */
    public function getOperatorBalance(string $paymentSystemName): array
    {
        $balanceResult = $this->getBalance();
        
        if (!$balanceResult['success']) {
            return $balanceResult;
        }

        foreach ($balanceResult['data'] as $balance) {
            if ($balance['payment_system_name'] === $paymentSystemName) {
                return [
                    'success' => true,
                    'data' => $balance
                ];
            }
        }

        return [
            'success' => false,
            'message' => "Opérateur {$paymentSystemName} non trouvé"
        ];
    }

    /**
     * Générer une référence externe unique
     */
    public function generateExternalReference(string $prefix = 'PAYOUT'): string
    {
        return $prefix . '_' . time() . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}