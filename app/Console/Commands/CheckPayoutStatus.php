<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PayoutService;

class CheckPayoutStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payout:check-status {--payout-id= : ID d\'un payout spécifique à vérifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier le statut des payouts en cours auprès de SHAP';

    /**
     * Execute the console command.
     */
    public function handle(PayoutService $payoutService): int
    {
        $this->info('🔍 Vérification des statuts de payouts...');

        $payoutId = $this->option('payout-id');

        if ($payoutId) {
            // Vérifier un payout spécifique
            $payout = \App\Models\Payout::find($payoutId);
            
            if (!$payout) {
                $this->error("❌ Payout avec l'ID {$payoutId} non trouvé");
                return 1;
            }

            $this->info("🔍 Vérification du payout {$payout->reference}...");
            $result = $payoutService->checkPayoutStatus($payout);

            if ($result['success']) {
                $this->info("✅ Statut: {$result['current_status']}");
                if ($result['is_final']) {
                    $this->info("🏁 Statut final atteint");
                } else {
                    $this->warn("⏳ Vérification supplémentaire nécessaire");
                }
            } else {
                $this->error("❌ Erreur: {$result['message']}");
            }

        } else {
            // Vérifier tous les payouts en cours
            $results = $payoutService->checkPendingPayouts();
            
            if (empty($results)) {
                $this->info("✅ Aucun payout en cours à vérifier");
                return 0;
            }

            $this->info("📊 Vérification de " . count($results) . " payout(s)...");
            
            $this->table(
                ['Payout ID', 'Référence', 'Statut', 'Succès'],
                array_map(function ($result) {
                    return [
                        $result['payout_id'],
                        $result['reference'],
                        $result['check_result']['current_status'] ?? 'Erreur',
                        $result['check_result']['success'] ? '✅' : '❌'
                    ];
                }, $results)
            );

            $successCount = count(array_filter($results, fn($r) => $r['check_result']['success']));
            $this->info("✅ {$successCount}/" . count($results) . " vérifications réussies");
        }

        return 0;
    }
}