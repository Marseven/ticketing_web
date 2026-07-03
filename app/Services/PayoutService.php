<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\OrganizerBalance;
use App\Models\Payout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Notifications\PayoutCreated;
use App\Notifications\PayoutSuccessful;
use App\Notifications\PayoutFailed;

class PayoutService
{
    private ShapPayoutService $shapPayoutService;

    public function __construct(ShapPayoutService $shapPayoutService)
    {
        $this->shapPayoutService = $shapPayoutService;
    }

    /**
     * Traiter le paiement réussi et gérer les soldes/payouts automatiques
     */
    public function processSuccessfulPayment(Payment $payment): void
    {
        try {
            $order = $payment->order;
            $organizer = $order->event->organizer;

            Log::info('Traitement paiement réussi pour payout', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'organizer_id' => $organizer->id,
                'amount' => $payment->amount,
                'gateway' => $payment->gateway
            ]);

            // 1. Créer ou mettre à jour le solde de l'organisateur (TOUJOURS crédité)
            // Transaction séparée pour garantir que le solde est crédité même si payout échoue
            DB::beginTransaction();
            $organizerBalance = $this->updateOrganizerBalance($organizer, $payment);
            DB::commit();

            Log::info('Solde organisateur crédité avec succès', [
                'organizer_id' => $organizer->id,
                'new_balance' => $organizerBalance->fresh()->balance
            ]);

            // 2. Mode de versement PAR ÉVÉNEMENT (décidé à la création).
            //    - instant  : on reverse immédiatement le net de CETTE vente
            //                 vers le numéro dédié de l'événement.
            //    - deferred : on n'envoie rien maintenant, le net s'accumule
            //                 et sera réglé en fin d'événement (commande
            //                 payout:settle-ended-events).
            $event = $order->event;

            if ($event && $event->isInstantPayout()) {
                $saleGateway = $organizerBalance->gateway;
                $netAmount = (float) $order->subtotal_amount;

                Log::info('⚡ Versement instantané déclenché (mode événement)', [
                    'organizer_id' => $organizer->id,
                    'event_id' => $event->id,
                    'gateway' => $saleGateway,
                    'net_amount' => $netAmount,
                    'payee' => $event->instant_payout_phone,
                ]);

                // Même mécanique que l'auto-payout global, mais ciblée sur le
                // numéro de l'événement et déclenchée par vente (montant exact
                // du net que l'on vient de créditer sur ce gateway).
                $this->createPayout(
                    $organizer,
                    $saleGateway,
                    $netAmount,
                    $event->instant_payout_phone,
                    true // is_automatic
                );
            } else {
                Log::info('🏦 Mode différé : net accumulé sur le solde (règlement en fin d\'événement)', [
                    'organizer_id' => $organizer->id,
                    'event_id' => $event?->id,
                    'balance' => $organizerBalance->fresh()->balance,
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur traitement paiement pour payout', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Mettre à jour le solde de l'organisateur
     */
    private function updateOrganizerBalance(Organizer $organizer, Payment $payment): OrganizerBalance
    {
        // Déduire le gateway depuis le paiement (airtelmoney ou moovmoney4)
        $gateway = $this->deduceGateway($payment);

        $organizerBalance = OrganizerBalance::firstOrCreate([
            'organizer_id' => $organizer->id,
            'gateway' => $gateway,
        ], [
            'balance' => 0,
            'pending_balance' => 0,
            'auto_payout_enabled' => false,
            'auto_payout_threshold' => 0,
        ]);

        // IMPORTANT: modèle DÉDUIT — subtotal_amount contient déjà le NET
        // reversé à l'organisateur (prix de base − commission retenue par la
        // plateforme). La commission (taux variable par événement) est stockée
        // dans order.fees_amount + order.commission_percentage.
        // Exemple: 4 billets × 1000 XAF, commission 10%
        //   Client paie 4000 XAF ; commission 400 ; organisateur reçoit 3600.
        $order = $payment->order;
        $netAmount = floatval($order->subtotal_amount);

        $organizerBalance->addBalance($netAmount);

        Log::info('Solde organisateur mis à jour', [
            'organizer_id' => $organizer->id,
            'gateway' => $gateway,
            'payment_system' => $payment->payment_system,
            'sub_payment_system' => $payment->sub_payment_system,
            'order_id' => $order->id,
            'total_paid_by_customer' => $payment->amount,
            'net_for_organizer' => $netAmount,
            'new_balance' => $organizerBalance->fresh()->balance
        ]);

        return $organizerBalance;
    }

    /**
     * Déduire le gateway depuis les champs payment_system, sub_payment_system
     * ou en dernier recours depuis le provider initial du paiement.
     */
    private function deduceGateway(Payment $payment): string
    {
        $subPaymentSystem = strtolower($payment->sub_payment_system ?? '');
        $paymentSystem = strtolower($payment->payment_system ?? '');

        // Mapping label -> gateway interne (ordre important: clés plus spécifiques en premier).
        $mapping = [
            'airtel money' => 'airtelmoney',
            'airtelmoney' => 'airtelmoney',
            'airtel' => 'airtelmoney',
            'am' => 'airtelmoney',
            'moov money' => 'moovmoney4',
            'moovmoney' => 'moovmoney4',
            'moov' => 'moovmoney4',
            'mm' => 'moovmoney4',
            'mc' => 'moovmoney4',
            'visa card' => 'ORABANK_NG',
            'mastercard' => 'ORABANK_NG',
            'visa' => 'ORABANK_NG',
            'orabank_ng' => 'ORABANK_NG',
            'orabank' => 'ORABANK_NG',
            'card' => 'ORABANK_NG',
        ];

        foreach ([$subPaymentSystem, $paymentSystem] as $source) {
            if ($source === '') {
                continue;
            }
            foreach ($mapping as $key => $gateway) {
                if (str_contains($source, $key)) {
                    Log::info('Gateway déduit depuis payment_system/sub_payment_system', [
                        'payment_id' => $payment->id,
                        'source' => $source,
                        'matched_key' => $key,
                        'deduced_gateway' => $gateway,
                    ]);
                    return $gateway;
                }
            }
        }

        // Fallback sur le provider initial du paiement (airtel, moov, card, bank)
        $providerFallback = match (strtolower($payment->provider ?? '')) {
            'airtel' => 'airtelmoney',
            'moov' => 'moovmoney4',
            'card' => 'ORABANK_NG',
            default => null,
        };

        if ($providerFallback !== null) {
            Log::warning('Gateway non identifié via payment_system - fallback sur provider', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'deduced_gateway' => $providerFallback,
            ]);
            return $providerFallback;
        }

        Log::error('Gateway impossible à déduire - utilisation airtelmoney par défaut', [
            'payment_id' => $payment->id,
            'provider' => $payment->provider,
            'payment_system' => $payment->payment_system,
            'sub_payment_system' => $payment->sub_payment_system,
        ]);

        return 'airtelmoney';
    }

    /**
     * Calculer le montant net après déduction des frais
     */
    private function calculateNetAmount(float $grossAmount, string $gateway): float
    {
        // Configuration des frais par gateway (à ajuster selon vos besoins)
        $feePercentages = [
            'airtelmoney' => 0.03, // 3%
            'moovmoney' => 0.03,   // 3%
            'ORABANK_NG' => 0.05,  // 5%
        ];

        $feePercentage = $feePercentages[$gateway] ?? 0.03;
        $fee = $grossAmount * $feePercentage;
        
        return $grossAmount - $fee;
    }

    /**
     * Déclencher un payout automatique
     */
    public function triggerAutomaticPayout(OrganizerBalance $organizerBalance): ?Payout
    {
        try {
            if (!$organizerBalance->shouldTriggerAutoPayout()) {
                return null;
            }

            Log::info('Déclenchement payout automatique', [
                'organizer_id' => $organizerBalance->organizer_id,
                'gateway' => $organizerBalance->gateway,
                'balance' => $organizerBalance->balance,
                'threshold' => $organizerBalance->auto_payout_threshold
            ]);

            return $this->createPayout(
                $organizerBalance->organizer,
                $organizerBalance->gateway,
                $organizerBalance->balance,
                $organizerBalance->phone_number,
                true // is_automatic
            );

        } catch (\Exception $e) {
            Log::error('Erreur payout automatique', [
                'organizer_balance_id' => $organizerBalance->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Créer un payout manuel
     */
    public function createManualPayout(
        Organizer $organizer,
        string $gateway,
        float $amount,
        string $phoneNumber
    ): array {
        try {
            Log::info('🎯 Début création payout manuel', [
                'organizer_id' => $organizer->id,
                'organizer_name' => $organizer->name,
                'gateway' => $gateway,
                'amount' => $amount,
                'phone_number' => $phoneNumber
            ]);

            // Vérifier le solde de l'organisateur pour ce gateway
            $organizerBalance = OrganizerBalance::where('organizer_id', $organizer->id)
                ->where('gateway', $gateway)
                ->first();

            if (!$organizerBalance || $organizerBalance->balance < $amount) {
                Log::warning('Solde organisateur insuffisant', [
                    'organizer_id' => $organizer->id,
                    'gateway' => $gateway,
                    'requested_amount' => $amount,
                    'available_balance' => $organizerBalance?->balance ?? 0
                ]);

                return [
                    'success' => false,
                    'message' => 'Solde insuffisant pour ce payout'
                ];
            }

            $payout = $this->createPayout($organizer, $gateway, $amount, $phoneNumber, false);

            if ($payout) {
                Log::info('✅ Payout manuel créé avec succès', [
                    'payout_id' => $payout->id,
                    'organizer_id' => $organizer->id,
                    'status' => $payout->status
                ]);

                return [
                    'success' => true,
                    'payout' => $payout,
                    'message' => 'Payout créé avec succès'
                ];
            }

            // Si createPayout retourne null, c'est qu'il y a eu une erreur (voir logs détaillés dans createPayout)
            Log::error('❌ Échec création payout manuel - createPayout returned null', [
                'organizer_id' => $organizer->id,
                'gateway' => $gateway,
                'amount' => $amount,
                'message' => 'La méthode createPayout() a retourné null - voir logs précédents pour détails'
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création du payout - voir logs pour détails'
            ];

        } catch (\Exception $e) {
            Log::error('💥 Exception payout manuel', [
                'organizer_id' => $organizer->id,
                'gateway' => $gateway,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la création du payout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Créer un payout (automatique ou manuel)
     */
    private function createPayout(
        Organizer $organizer,
        string $gateway,
        float $amount,
        string $phoneNumber,
        bool $isAutomatic
    ): ?Payout {
        try {
            DB::beginTransaction();

            // 1. Créer l'enregistrement payout
            $payout = Payout::create([
                'organizer_id' => $organizer->id,
                'reference' => Payout::generateReference(),
                'external_reference' => $this->shapPayoutService->generateExternalReference(),
                'gateway' => $gateway,
                'payment_system_name' => $this->shapPayoutService->getPaymentSystemName($gateway),
                'payee_msisdn' => $phoneNumber,
                'amount' => $amount,
                'payout_type' => 'withdrawal',
                'status' => 'pending',
                'is_automatic' => $isAutomatic,
            ]);

            // 2. Déduire le montant du solde de l'organisateur
            $organizerBalance = OrganizerBalance::where('organizer_id', $organizer->id)
                ->where('gateway', $gateway)
                ->first();

            if (!$organizerBalance || !$organizerBalance->deductBalance($amount)) {
                throw new \Exception('Impossible de déduire le montant du solde');
            }

            // 3. Créer le payout via SHAP
            Log::info('🚀 SHAP API Call - Creating Payout via ShapPayoutService', [
                'payout_id' => $payout->id,
                'organizer_id' => $organizer->id,
                'organizer_name' => $organizer->name,
                'payment_system_name' => $payout->payment_system_name,
                'payee_msisdn' => $payout->payee_msisdn,
                'amount' => $payout->amount,
                'external_reference' => $payout->external_reference,
                'payout_type' => $payout->payout_type,
                'gateway' => $gateway,
                'is_automatic' => $isAutomatic
            ]);

            $shapResult = $this->shapPayoutService->createPayout(
                $payout->payment_system_name,
                $payout->payee_msisdn,
                $payout->amount,
                $payout->external_reference,
                $payout->payout_type
            );

            Log::info('📥 SHAP API Response - Payout Creation Result', [
                'payout_id' => $payout->id,
                'organizer_id' => $organizer->id,
                'success' => $shapResult['success'] ?? false,
                'is_synchronous' => $shapResult['is_synchronous'] ?? null,
                'requires_status_check' => $shapResult['requires_status_check'] ?? false,
                'shap_status' => $shapResult['shap_status'] ?? null,
                'shap_payout_id' => $shapResult['data']['payout_id'] ?? null,
                'full_response' => $shapResult
            ]);

            if ($shapResult['success']) {
                if ($shapResult['is_synchronous']) {
                    // Payout synchrone réussi
                    $payout->markAsSuccess($shapResult['data']);
                    Log::info('Payout synchrone réussi', [
                        'payout_id' => $payout->id,
                        'shap_payout_id' => $shapResult['data']['payout_id'] ?? null
                    ]);
                } else {
                    // Payout asynchrone, marquer comme en cours
                    $payout->markAsProcessing($shapResult['data']);
                    Log::info('Payout asynchrone créé, vérification de statut requise', [
                        'payout_id' => $payout->id,
                        'shap_payout_id' => $shapResult['data']['payout_id'] ?? null,
                        'requires_status_check' => $shapResult['requires_status_check']
                    ]);
                    
                    // Programmer une vérification de statut si nécessaire
                    if ($shapResult['requires_status_check']) {
                        $this->scheduleStatusCheck($payout);
                    }
                }
            } else {
                // Remettre le montant dans le solde si le payout SHAP échoue
                $organizerBalance->addBalance($amount);
                $payout->markAsFailed($shapResult['message'], $shapResult);
                
                Log::error('Échec création payout SHAP', [
                    'payout_id' => $payout->id,
                    'error' => $shapResult['message']
                ]);
            }

            DB::commit();

            // Envoyer la notification PayoutCreated uniquement si le payout n'a pas échoué immédiatement
            if ($payout && $payout->status !== 'failed') {
                $this->notifyOrganizerMembers($organizer, new PayoutCreated($payout), 'PayoutCreated', $payout->id);
            }

            return $payout;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création payout', [
                'organizer_id' => $organizer->id,
                'gateway' => $gateway,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Traiter les callbacks de payout SHAP
     */
    public function handlePayoutCallback(array $callbackData): void
    {
        try {
            Log::info('📨 SHAP Webhook - Payout Callback Received', [
                'external_reference' => $callbackData['external_reference'] ?? null,
                'status' => $callbackData['status'] ?? null,
                'full_callback_data' => $callbackData,
                'timestamp' => now()->toIso8601String()
            ]);

            $payout = Payout::where('external_reference', $callbackData['external_reference'])->first();

            if (!$payout) {
                Log::warning('⚠️ SHAP Webhook - Payout Not Found', [
                    'external_reference' => $callbackData['external_reference'] ?? null,
                    'callback_data' => $callbackData
                ]);
                return;
            }

            Log::info('✅ SHAP Webhook - Processing Payout Callback', [
                'payout_id' => $payout->id,
                'organizer_id' => $payout->organizer_id,
                'current_status' => $payout->status,
                'callback_status' => $callbackData['status'] ?? null,
                'amount' => $payout->amount,
                'callback_data' => $callbackData
            ]);

            if ($callbackData['status'] === 'success') {
                $payout->markAsSuccess($callbackData);

                Log::info('✅ SHAP Webhook - Payout Marked as Success', [
                    'payout_id' => $payout->id,
                    'organizer_id' => $payout->organizer_id,
                    'amount' => $payout->amount,
                    'final_status' => 'success'
                ]);

                if ($payout->organizer) {
                    $this->notifyOrganizerMembers($payout->organizer, new PayoutSuccessful($payout), 'PayoutSuccessful', $payout->id);
                }
            } else {
                // Remettre le montant dans le solde si le payout échoue
                $organizerBalance = OrganizerBalance::where('organizer_id', $payout->organizer_id)
                    ->where('gateway', $payout->gateway)
                    ->first();

                if ($organizerBalance) {
                    $oldBalance = $organizerBalance->balance;
                    $organizerBalance->addBalance($payout->amount);

                    Log::info('💰 Payout Failed - Balance Refunded to Organizer', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id,
                        'refunded_amount' => $payout->amount,
                        'old_balance' => $oldBalance,
                        'new_balance' => $organizerBalance->fresh()->balance
                    ]);
                }

                $payout->markAsFailed('Payout échoué côté SHAP', $callbackData);

                Log::error('❌ SHAP Webhook - Payout Marked as Failed', [
                    'payout_id' => $payout->id,
                    'organizer_id' => $payout->organizer_id,
                    'amount' => $payout->amount,
                    'callback_status' => $callbackData['status'] ?? null,
                    'error_message' => 'Payout échoué côté SHAP',
                    'callback_data' => $callbackData
                ]);

                if ($payout->organizer) {
                    $this->notifyOrganizerMembers($payout->organizer, new PayoutFailed($payout), 'PayoutFailed', $payout->id);
                }
            }

        } catch (\Exception $e) {
            Log::error('💥 Exception - SHAP Webhook Callback Processing Failed', [
                'callback_data' => $callbackData,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Marquer un payout comme nécessitant un polling.
     *
     * Aucun dispatch synchrone ici: l'hébergement mutualisé (Hostinger)
     * n'expose pas de queue worker permanent. Le scheduler Laravel exécute
     * `php artisan payout:check-status` toutes les 5 minutes via un unique
     * cron job, qui appelle PayoutService::checkPendingPayouts() et balaie
     * tous les payouts en statut pending/processing.
     */
    private function scheduleStatusCheck(Payout $payout): void
    {
        Log::info('Payout asynchrone enregistré pour polling cron', [
            'payout_id' => $payout->id,
            'external_reference' => $payout->external_reference,
        ]);
    }

    /**
     * Vérifier le statut d'un payout auprès de SHAP
     */
    public function checkPayoutStatus(Payout $payout): array
    {
        try {
            Log::info('🔍 SHAP API Call - Checking Payout Status', [
                'payout_id' => $payout->id,
                'organizer_id' => $payout->organizer_id,
                'external_reference' => $payout->external_reference,
                'payee_msisdn' => $payout->payee_msisdn,
                'current_status' => $payout->status,
                'amount' => $payout->amount
            ]);

            $statusResult = $this->shapPayoutService->checkPayoutStatus(
                $payout->payee_msisdn,
                $payout->external_reference
            );

            Log::info('📥 SHAP API Response - Payout Status Check Result', [
                'payout_id' => $payout->id,
                'organizer_id' => $payout->organizer_id,
                'success' => $statusResult['success'] ?? false,
                'shap_status' => $statusResult['shap_status'] ?? null,
                'normalized_status' => $statusResult['normalized_status'] ?? null,
                'is_final' => $statusResult['is_final'] ?? false,
                'full_response' => $statusResult
            ]);

            if (!$statusResult['success']) {
                Log::warning('⚠️ SHAP API - Payout Status Check Failed', [
                    'payout_id' => $payout->id,
                    'external_reference' => $payout->external_reference,
                    'error_message' => $statusResult['message'] ?? 'Unknown error',
                    'full_response' => $statusResult
                ]);

                return [
                    'success' => false,
                    'message' => $statusResult['message']
                ];
            }

            $currentStatus = $payout->status;
            $newStatus = $statusResult['normalized_status'];

            Log::info('Vérification statut payout', [
                'payout_id' => $payout->id,
                'current_status' => $currentStatus,
                'shap_status' => $statusResult['shap_status'],
                'new_status' => $newStatus,
                'is_final' => $statusResult['is_final']
            ]);

            // Mettre à jour le statut si il a changé
            if ($currentStatus !== $newStatus) {
                $this->updatePayoutStatus($payout, $newStatus, $statusResult['data']);
            }

            return [
                'success' => true,
                'current_status' => $newStatus,
                'is_final' => $statusResult['is_final'],
                'shap_data' => $statusResult['data']
            ];

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut payout', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la vérification du statut'
            ];
        }
    }

    /**
     * Mettre à jour le statut d'un payout selon le statut SHAP
     */
    private function updatePayoutStatus(Payout $payout, string $newStatus, array $shapData): void
    {
        try {
            DB::beginTransaction();

            Log::info('🔄 Updating Payout Status', [
                'payout_id' => $payout->id,
                'organizer_id' => $payout->organizer_id,
                'old_status' => $payout->status,
                'new_status' => $newStatus,
                'amount' => $payout->amount,
                'shap_data' => $shapData
            ]);

            switch ($newStatus) {
                case 'success':
                    $payout->markAsSuccess($shapData);

                    Log::info('✅ Payout Status Updated - Success', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id,
                        'amount' => $payout->amount
                    ]);
                    break;

                case 'failed':
                    // Remettre le montant dans le solde de l'organisateur
                    $organizerBalance = OrganizerBalance::where('organizer_id', $payout->organizer_id)
                        ->where('gateway', $payout->gateway)
                        ->first();

                    if ($organizerBalance) {
                        $oldBalance = $organizerBalance->balance;
                        $organizerBalance->addBalance($payout->amount);

                        Log::info('💰 Balance Refunded to Organizer', [
                            'payout_id' => $payout->id,
                            'organizer_id' => $payout->organizer_id,
                            'refunded_amount' => $payout->amount,
                            'old_balance' => $oldBalance,
                            'new_balance' => $organizerBalance->fresh()->balance
                        ]);
                    }

                    $payout->markAsFailed('Payout échoué côté SHAP', $shapData);

                    Log::error('❌ Payout Status Updated - Failed', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id,
                        'amount' => $payout->amount,
                        'shap_data' => $shapData
                    ]);
                    break;

                case 'processing':
                    $payout->markAsProcessing($shapData);

                    Log::info('⏳ Payout Status Updated - Processing', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id,
                        'amount' => $payout->amount
                    ]);
                    break;

                default:
                    Log::warning('⚠️ Unhandled Payout Status', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id,
                        'status' => $newStatus,
                        'shap_data' => $shapData
                    ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('💥 Exception - Payout Status Update Failed', [
                'payout_id' => $payout->id,
                'organizer_id' => $payout->organizer_id,
                'old_status' => $payout->status,
                'attempted_new_status' => $newStatus,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Vérifier les statuts de tous les payouts en cours
     */
    public function checkPendingPayouts(): array
    {
        $pendingPayouts = Payout::whereIn('status', ['pending', 'processing'])->get();
        $results = [];

        foreach ($pendingPayouts as $payout) {
            $result = $this->checkPayoutStatus($payout);
            $results[] = [
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'check_result' => $result
            ];
        }

        Log::info('Vérification batch payouts terminée', [
            'total_checked' => count($results),
            'results' => $results
        ]);

        return $results;
    }

    /**
     * Régler un événement en mode différé (versement en fin d'événement).
     *
     * Logique TOUT-OU-RIEN pour garantir l'idempotence : on ne marque
     * l'événement comme réglé (payout_settled_at) que si TOUTES les parts
     * par gateway ont pu être versées en une seule passe. Sinon on ne verse
     * rien et on laisse l'événement pour la passe suivante / l'admin — évitant
     * tout double-paiement sur une exécution partielle.
     *
     * @return array Détail du règlement (settled, payouts, reason).
     */
    public function settleDeferredEvent(Event $event): array
    {
        // Net accumulé par gateway, à partir des commandes payées de l'événement.
        $orders = Order::where('organizer_id', $event->organizer_id)
            ->whereHas('tickets', fn ($q) => $q->where('event_id', $event->id))
            ->where('status', 'paid')
            ->with('payments')
            ->get();

        $netByGateway = [];
        foreach ($orders as $order) {
            $payment = $order->payments->firstWhere('status', 'success');
            if (!$payment) {
                continue;
            }
            $gw = $this->deduceGateway($payment);
            $netByGateway[$gw] = ($netByGateway[$gw] ?? 0) + (float) $order->subtotal_amount;
        }

        // Aucun revenu à régler : on marque réglé pour ne plus le repasser.
        if (empty($netByGateway)) {
            $event->payout_settled_at = now();
            $event->save();
            return ['settled' => true, 'payouts' => [], 'reason' => 'no_revenue'];
        }

        // 1) Pré-vérification : chaque part doit être intégralement payable.
        $plan = [];
        foreach ($netByGateway as $gw => $net) {
            $net = round($net, 2);
            if ($net <= 0) {
                continue;
            }
            $balance = OrganizerBalance::where('organizer_id', $event->organizer_id)
                ->where('gateway', $gw)
                ->first();

            if (!$balance || empty($balance->phone_number) || (float) $balance->balance < $net) {
                $reason = !$balance || empty($balance->phone_number)
                    ? 'missing_payout_number'
                    : 'insufficient_balance';

                Log::warning('Règlement différé impossible (part non payable) — laissé pour l\'admin', [
                    'event_id' => $event->id,
                    'gateway' => $gw,
                    'net' => $net,
                    'reason' => $reason,
                ]);

                return ['settled' => false, 'payouts' => [], 'reason' => $reason, 'gateway' => $gw];
            }

            $plan[] = ['gateway' => $gw, 'net' => $net, 'phone' => $balance->phone_number];
        }

        // 2) Exécution : toutes les parts sont payables.
        $payouts = [];
        foreach ($plan as $p) {
            $payout = $this->createPayout(
                $event->organizer,
                $p['gateway'],
                $p['net'],
                $p['phone'],
                true // is_automatic
            );

            if ($payout) {
                $payouts[] = ['gateway' => $p['gateway'], 'amount' => $p['net'], 'payout_id' => $payout->id];
            } else {
                // Un échec en cours d'exécution : on log mais on ne re-verse pas
                // les parts déjà envoyées (le solde a été déduit atomiquement).
                Log::error('Échec createPayout pendant règlement différé', [
                    'event_id' => $event->id,
                    'gateway' => $p['gateway'],
                    'net' => $p['net'],
                ]);
            }
        }

        $event->payout_settled_at = now();
        $event->save();

        Log::info('✅ Événement différé réglé', [
            'event_id' => $event->id,
            'payouts' => $payouts,
        ]);

        return ['settled' => true, 'payouts' => $payouts, 'reason' => 'ok'];
    }

    /**
     * Régler tous les événements différés terminés et non encore réglés.
     */
    public function settleEndedDeferredEvents(): array
    {
        $events = Event::where('payout_mode', 'deferred')
            ->whereNull('payout_settled_at')
            ->whereHas('schedules')
            ->with('organizer')
            ->get()
            ->filter(fn (Event $e) => $e->hasEnded());

        $summary = [];
        foreach ($events as $event) {
            $summary[] = array_merge(['event_id' => $event->id], $this->settleDeferredEvent($event));
        }

        return $summary;
    }

    /**
     * Obtenir les soldes d'un organisateur
     */
    public function getOrganizerBalances(Organizer $organizer): array
    {
        return OrganizerBalance::where('organizer_id', $organizer->id)
            ->get()
            ->map(function ($balance) {
                return [
                    'gateway' => $balance->gateway,
                    'gateway_display_name' => $balance->gateway_display_name,
                    'balance' => $balance->balance,
                    'pending_balance' => $balance->pending_balance,
                    'auto_payout_enabled' => $balance->auto_payout_enabled,
                    'auto_payout_threshold' => $balance->auto_payout_threshold,
                    'phone_number' => $balance->phone_number,
                ];
            })->toArray();
    }

    /**
     * Notifier tous les utilisateurs rattachés à un organizer.
     *
     * Un Organizer peut avoir plusieurs membres via la table pivot
     * organizer_user — on les notifie tous (pas seulement le "owner"),
     * et on isole chaque envoi pour qu'une exception sur un destinataire
     * ne bloque pas les autres.
     */
    private function notifyOrganizerMembers(Organizer $organizer, $notification, string $label, ?int $payoutId = null): void
    {
        $members = $organizer->users()->get();

        if ($members->isEmpty()) {
            Log::warning("Notification {$label} non envoyée - aucun user rattaché à l'organizer", [
                'payout_id' => $payoutId,
                'organizer_id' => $organizer->id,
            ]);
            return;
        }

        foreach ($members as $user) {
            try {
                $user->notify($notification);
                Log::info("Notification {$label} envoyée", [
                    'payout_id' => $payoutId,
                    'organizer_id' => $organizer->id,
                    'user_id' => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Erreur envoi notification {$label}", [
                    'payout_id' => $payoutId,
                    'organizer_id' => $organizer->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}