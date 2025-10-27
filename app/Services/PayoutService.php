<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
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

            // 2. Vérifier si un payout automatique doit être déclenché
            // Cette étape est indépendante - si elle échoue, le solde reste crédité
            if ($organizerBalance->shouldTriggerAutoPayout()) {
                Log::info('🔔 Payout automatique déclenché - seuil atteint', [
                    'organizer_id' => $organizer->id,
                    'balance' => $organizerBalance->fresh()->balance,
                    'threshold' => $organizerBalance->auto_payout_threshold,
                    'gateway' => $payment->gateway
                ]);

                $this->triggerAutomaticPayout($organizerBalance);
            } else {
                Log::info('ℹ️ Payout automatique non déclenché', [
                    'organizer_id' => $organizer->id,
                    'balance' => $organizerBalance->fresh()->balance,
                    'threshold' => $organizerBalance->auto_payout_threshold,
                    'auto_payout_enabled' => $organizerBalance->auto_payout_enabled,
                    'reason' => !$organizerBalance->auto_payout_enabled ? 'Auto-payout désactivé' : 'Seuil non atteint'
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
        // Vérifier que le gateway n'est pas null
        $gateway = $payment->gateway ?? 'unknown';

        if ($gateway === 'unknown' || empty($gateway)) {
            Log::warning('Gateway null ou vide lors de la mise à jour du solde', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway_original' => $payment->gateway
            ]);
            // Utiliser un gateway par défaut basé sur le mode de paiement
            $gateway = 'airtelmoney'; // Par défaut
        }

        $organizerBalance = OrganizerBalance::firstOrCreate([
            'organizer_id' => $organizer->id,
            'gateway' => $gateway,
        ], [
            'balance' => 0,
            'pending_balance' => 0,
            'auto_payout_enabled' => false,
            'auto_payout_threshold' => 0,
        ]);

        // IMPORTANT: subtotal_amount = montant BRUT que l'organisateur reçoit
        // C'est 100% du prix de base (prix × quantité) défini par l'organisateur
        // Les frais (5%) et la TVA (18%) sont ajoutés au total payé par le client
        // Exemple: 4 billets × 1000 XAF = 4000 XAF pour l'organisateur
        //         Client paie: 4000 + (5% frais) + (18% TVA sur frais) = 4236 XAF
        $order = $payment->order;
        $netAmount = floatval($order->subtotal_amount);

        $organizerBalance->addBalance($netAmount);

        Log::info('Solde organisateur mis à jour', [
            'organizer_id' => $organizer->id,
            'gateway' => $payment->gateway,
            'order_id' => $order->id,
            'total_paid_by_customer' => $payment->amount,
            'net_for_organizer' => $netAmount,
            'new_balance' => $organizerBalance->fresh()->balance
        ]);

        return $organizerBalance;
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
            // Vérifier le solde de l'organisateur
            $organizerBalance = OrganizerBalance::where('organizer_id', $organizer->id)
                ->where('gateway', $gateway)
                ->first();

            if (!$organizerBalance || $organizerBalance->balance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant pour ce payout'
                ];
            }

            $payout = $this->createPayout($organizer, $gateway, $amount, $phoneNumber, false);

            if ($payout) {
                return [
                    'success' => true,
                    'payout' => $payout,
                    'message' => 'Payout créé avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de la création du payout'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur payout manuel', [
                'organizer_id' => $organizer->id,
                'gateway' => $gateway,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la création du payout'
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
                try {
                    $organizer->user->notify(new PayoutCreated($payout));
                    Log::info('Notification PayoutCreated envoyée', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $organizer->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification PayoutCreated', [
                        'payout_id' => $payout->id,
                        'error' => $e->getMessage()
                    ]);
                }
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

                // Envoyer la notification PayoutSuccessful
                try {
                    $payout->organizer->user->notify(new PayoutSuccessful($payout));
                    Log::info('Notification PayoutSuccessful envoyée', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification PayoutSuccessful', [
                        'payout_id' => $payout->id,
                        'error' => $e->getMessage()
                    ]);
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

                // Envoyer la notification PayoutFailed
                try {
                    $payout->organizer->user->notify(new PayoutFailed($payout));
                    Log::info('Notification PayoutFailed envoyée', [
                        'payout_id' => $payout->id,
                        'organizer_id' => $payout->organizer_id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur envoi notification PayoutFailed', [
                        'payout_id' => $payout->id,
                        'error' => $e->getMessage()
                    ]);
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
     * Programmer une vérification de statut pour un payout asynchrone
     */
    private function scheduleStatusCheck(Payout $payout): void
    {
        // Pour l'instant, on log simplement. Dans une vraie app, on utiliserait
        // Laravel Queue/Jobs pour programmer des vérifications périodiques
        Log::info('Programmation vérification statut payout', [
            'payout_id' => $payout->id,
            'external_reference' => $payout->external_reference,
            'check_needed_in' => '5 minutes'
        ]);
        
        // TODO: Implémenter avec Laravel Queue
        // dispatch(new CheckPayoutStatusJob($payout))->delay(now()->addMinutes(5));
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
}