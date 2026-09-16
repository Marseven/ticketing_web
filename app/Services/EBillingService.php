<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EBillingService
{
    private string $username;
    private string $sharedKey;
    private string $serverUrl;
    private string $postUrl;

    // OAuth (AWS Cognito, flow client_credentials) — optionnel, activé via
    // EBILLING_AUTH_MODE=oauth. Basic reste le fallback.
    private string $authMode;
    private string $oauthTokenUrl;
    private string $oauthClientId;
    private string $oauthClientSecret;
    private string $oauthScope;

    /** Clé de cache du token OAuth e-billing (dépôt). */
    private const TOKEN_CACHE_KEY = 'ebilling_oauth_token';

    public function __construct()
    {
        $this->username = env('EBILLING_USERNAME') ?? throw new \Exception('EBILLING_USERNAME n\'est pas configuré dans .env');
        $this->sharedKey = env('EBILLING_SHARED_KEY') ?? throw new \Exception('EBILLING_SHARED_KEY n\'est pas configuré dans .env');
        $this->serverUrl = env('EBILLING_SERVER_URL') ?? throw new \Exception('EBILLING_SERVER_URL n\'est pas configuré dans .env');
        $this->postUrl = env('EBILLING_POST_URL') ?? throw new \Exception('EBILLING_POST_URL n\'est pas configuré dans .env');

        // Mode d'authentification : 'oauth' (Cognito) ou 'basic' (défaut sûr).
        $this->authMode = strtolower((string) env('EBILLING_AUTH_MODE', 'basic'));
        $this->oauthTokenUrl = (string) env('EBILLING_OAUTH_TOKEN_URL', '');
        $this->oauthClientId = (string) env('EBILLING_OAUTH_CLIENT_ID', '');
        $this->oauthClientSecret = (string) env('EBILLING_OAUTH_CLIENT_SECRET', '');
        $this->oauthScope = (string) env('EBILLING_OAUTH_SCOPE', '');
    }

    // ---------------------------------------------------------------------
    //  Authentification (OAuth Cognito avec fallback Basic)
    // ---------------------------------------------------------------------

    private function usesOAuth(): bool
    {
        return $this->authMode === 'oauth';
    }

    /** Requête HTTP JSON pré-authentifiée selon le mode demandé. */
    private function request(string $mode): PendingRequest
    {
        $req = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        if ($mode === 'oauth') {
            return $req->withToken($this->oauthToken());
        }

        return $req->withBasicAuth($this->username, $this->sharedKey);
    }

    /** Token OAuth e-billing, mis en cache et régénéré avant expiration. */
    private function oauthToken(bool $force = false): string
    {
        if ($force) {
            OAuthTokenStore::forget(self::TOKEN_CACHE_KEY);
        }

        return OAuthTokenStore::token(self::TOKEN_CACHE_KEY, fn () => $this->fetchCognitoToken());
    }

    /**
     * Récupère un token via AWS Cognito (grant client_credentials).
     * Identifiants client en en-tête Basic (client confidentiel).
     * @return array{access_token:string, expires_in:int}
     */
    private function fetchCognitoToken(): array
    {
        if ($this->oauthTokenUrl === '' || $this->oauthClientId === '' || $this->oauthClientSecret === '') {
            throw new \RuntimeException('OAuth e-billing non configuré (EBILLING_OAUTH_TOKEN_URL / CLIENT_ID / CLIENT_SECRET).');
        }

        $response = Http::asForm()
            ->withBasicAuth($this->oauthClientId, $this->oauthClientSecret)
            ->post($this->oauthTokenUrl, array_filter([
                'grant_type' => 'client_credentials',
                'scope' => $this->oauthScope !== '' ? $this->oauthScope : null,
            ]));

        if (!$response->successful()) {
            Log::error('E-Billing OAuth (Cognito) - échec token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Échec obtention token OAuth e-billing (Cognito) : ' . $response->status());
        }

        return $response->json();
    }

    /**
     * Exécute un appel avec l'auth du mode courant ; en mode OAuth, retombe
     * automatiquement sur Basic si l'OAuth échoue (401/403 ou indisponible) —
     * les paiements ne cassent jamais à cause de l'auth.
     *
     * @param  callable(PendingRequest):Response $call
     */
    private function sendWithFallback(callable $call): Response
    {
        if ($this->usesOAuth()) {
            try {
                $response = $call($this->request('oauth'));
                if (!in_array($response->status(), [401, 403], true)) {
                    return $response;
                }
                OAuthTokenStore::forget(self::TOKEN_CACHE_KEY);
                Log::warning('E-Billing OAuth non autorisé — fallback Basic', ['status' => $response->status()]);
            } catch (\Throwable $e) {
                Log::warning('E-Billing OAuth indisponible — fallback Basic', ['error' => $e->getMessage()]);
            }
        }

        return $call($this->request('basic'));
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

            $response = $this->sendWithFallback(
                fn (PendingRequest $req) => $req->post($this->serverUrl, $data)
            );

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

            Log::info('🚀 E-Billing API Call - Push USSD', [
                'url' => $url,
                'bill_id' => $billId,
                'payload' => $payload,
                'auth_username' => $this->username,
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            $startTime = microtime(true);

            $response = $this->sendWithFallback(
                fn (PendingRequest $req) => $req->timeout(30)->post($url, $payload)
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $status = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('✅ E-Billing API Response - Push USSD', [
                'status' => $status,
                'duration_ms' => $duration,
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
            $isTimeout = str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out');

            Log::error('💥 E-Billing pushUSSD error' . ($isTimeout ? ' (TIMEOUT)' : ''), [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'is_timeout' => $isTimeout,
                'bill_id' => $billId,
                'payment_system' => $paymentSystem,
                'msisdn' => $msisdn,
                'url' => $url ?? null,
                'payload_sent' => $payload ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du push USSD',
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

            $response = $this->sendWithFallback(
                fn (PendingRequest $req) => $req->get($url, [
                    'payment_system_name' => $paymentSystem,
                    'msisdn' => $msisdn,
                ])
            );

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

            $response = $this->sendWithFallback(
                fn (PendingRequest $req) => $req->get($url)
            );

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
     *
     * Note: Les noms retournés doivent correspondre à ceux acceptés par l'API E-Billing
     * pour les appels push USSD et KYC.
     *
     * Codes E-Billing observés dans les webhooks:
     * - AM = Airtel Money
     * - MM = Moov Money (nouveau)
     * - MC = MobiCash/Moov Money (ancien)
     *
     * @param string $gateway Le gateway interne (airtelmoney, moovmoney4, etc.)
     * @return string Le nom du système de paiement pour E-Billing API
     */
    public function getPaymentSystemName(string $gateway): string
    {
        return match($gateway) {
            // Airtel Money - fonctionne avec le nom complet
            'airtelmoney', 'airtel' => 'airtelmoney',

            // Moov Money - utiliser 'moovmoney4' (confirmé par logs de production)
            // L'API E-Billing timeout avec 'moovmoney', fonctionne avec 'moovmoney4'
            'moovmoney', 'moov', 'moovmoney4' => 'moovmoney4',

            // Visa/Mastercard
            'visa', 'card', 'ORABANK_NG' => 'VISA',

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