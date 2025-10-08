<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EBillingService
{
    private string $username;
    private string $sharedKey;
    private string $serverUrl;
    private string $postUrl;

    public function __construct()
    {
        $this->username = env('EBILLING_USERNAME') ?? throw new \Exception('EBILLING_USERNAME n\'est pas configuré dans .env');
        $this->sharedKey = env('EBILLING_SHARED_KEY') ?? throw new \Exception('EBILLING_SHARED_KEY n\'est pas configuré dans .env');
        $this->serverUrl = env('EBILLING_SERVER_URL') ?? throw new \Exception('EBILLING_SERVER_URL n\'est pas configuré dans .env');
        $this->postUrl = env('EBILLING_POST_URL') ?? throw new \Exception('EBILLING_POST_URL n\'est pas configuré dans .env');
    }

    /**
     * Créer une facture E-Billing
     */
    public function createBill(array $data): array
    {
        try {
            $auth = $this->username . ':' . $this->sharedKey;

            Log::info('E-Billing createBill - Request', [
                'url' => $this->serverUrl,
                'username' => $this->username,
                'has_shared_key' => !empty($this->sharedKey),
                'data' => $data
            ]);

            $response = Http::withBasicAuth($this->username, $this->sharedKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->serverUrl, $data);

            $status = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('E-Billing createBill - Response', [
                'status' => $status,
                'response_raw' => $responseBody,
                'response_json' => $responseData,
                'response_headers' => $response->headers(),
                'content_type' => $response->header('Content-Type'),
            ]);

            if ($status >= 200 && $status <= 299) {
                return [
                    'success' => true,
                    'bill_id' => $responseData['e_bill']['bill_id'] ?? null,
                    'data' => $responseData,
                    'post_url' => $this->postUrl
                ];
            }

            Log::warning('E-Billing createBill - Non-success status', [
                'status' => $status,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création de la facture E-Billing',
                'error' => $responseData['message'] ?? $responseData['error'] ?? 'Erreur inconnue',
                'error_details' => $responseData,
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('E-Billing createBill - Exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion avec E-Billing',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un push USSD
     */
    public function pushUSSD(string $billId, string $paymentSystem, string $msisdn): array
    {
        try {
            $url = rtrim($this->serverUrl, '/e_bills') . '/e_bills/' . $billId . '/ussd_push';

            $payload = [
                'payment_system_name' => $paymentSystem,
                'payer_msisdn' => $msisdn
            ];

            Log::info('E-Billing API Call - Push USSD', [
                'url' => $url,
                'bill_id' => $billId,
                'payload' => $payload,
            ]);

            $response = Http::timeout(30) // Timeout de 30s pour le push USSD
                ->withBasicAuth($this->username, $this->sharedKey)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $status = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('E-Billing API Response - Push USSD', [
                'status' => $status,
                'response_raw' => $responseBody,
                'response_json' => $responseData,
                'response_headers' => $response->headers(),
                'content_type' => $response->header('Content-Type'),
            ]);

            if ($status >= 200 && $status <= 299) {
                return [
                    'success' => true,
                    'message' => $responseData['message'] ?? 'Push envoyé',
                    'data' => $responseData
                ];
            }

            Log::warning('E-Billing pushUSSD - Non-success status', [
                'status' => $status,
                'response' => $responseData,
                'bill_id' => $billId
            ]);

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Erreur lors du push USSD',
                'status' => $status,
                'details' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('E-Billing pushUSSD error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'bill_id' => $billId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors du push USSD: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier KYC
     */
    public function checkKYC(string $paymentSystem, string $msisdn): array
    {
        try {
            $url = rtrim($this->serverUrl, '/e_bills') . '/kyc';

            Log::info('E-Billing API Call - Check KYC', [
                'url' => $url,
                'payment_system' => $paymentSystem,
                'msisdn' => $msisdn,
            ]);

            $response = Http::withBasicAuth($this->username, $this->sharedKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($url, [
                    'payment_system_name' => $paymentSystem,
                    'msisdn' => $msisdn
                ]);

            $status = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('E-Billing API Response - Check KYC', [
                'status' => $status,
                'response_raw' => $responseBody,
                'response_json' => $responseData,
                'response_headers' => $response->headers(),
                'content_type' => $response->header('Content-Type'),
            ]);

            if ($status === 200) {
                return [
                    'success' => true,
                    'customer_name' => $responseData['key_data']['payer_name'] ?? null,
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => 'Client non trouvé',
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('E-Billing KYC error', [
                'error' => $e->getMessage(),
                'msisdn' => $msisdn
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification KYC',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'une facture E-Billing
     */
    public function getBillStatus(string $billId): array
    {
        try {
            $url = rtrim($this->serverUrl, '/e_bills') . '/e_bills/' . $billId;

            Log::info('E-Billing API Call - Get Bill Status', [
                'url' => $url,
                'bill_id' => $billId,
            ]);

            $response = Http::withBasicAuth($this->username, $this->sharedKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $status = $response->status();
            $responseData = $response->json();

            Log::info('E-Billing API Response - Get Bill Status', [
                'status' => $status,
                'response_json' => $responseData,
            ]);

            if ($status === 200) {
                return [
                    'success' => true,
                    'bill_status' => $responseData['e_bill']['state'] ?? null,
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le statut de la facture',
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('E-Billing getBillStatus error', [
                'error' => $e->getMessage(),
                'bill_id' => $billId
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le nom du système de paiement à partir du gateway
     */
    public function getPaymentSystemName(string $gateway): string
    {
        return match($gateway) {
            'airtelmoney', 'airtel' => 'airtelmoney',
            'moovmoney', 'moov', 'moovmoney4' => 'moovmoney4',
            'visa', 'card' => 'VISA',
            default => strtoupper($gateway)
        };
    }

    /**
     * Formater le numéro de téléphone pour E-Billing
     */
    public function formatPhoneNumber(string $phone): string
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
}