<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;

class SettleEndedEvents extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payout:settle-ended-events';

    /**
     * @var string
     */
    protected $description = 'Régler les événements en mode différé dont toutes les dates sont passées (versement en fin d\'événement)';

    public function handle(PayoutService $payoutService): int
    {
        $this->info('🏦 Règlement des événements différés terminés…');

        $results = $payoutService->settleEndedDeferredEvents();

        if (empty($results)) {
            $this->info('✅ Aucun événement différé à régler.');
            return self::SUCCESS;
        }

        $settled = 0;
        $skipped = 0;
        foreach ($results as $r) {
            if (($r['settled'] ?? false) === true) {
                $settled++;
                $this->line("  ✔ Événement #{$r['event_id']} réglé (" . count($r['payouts'] ?? []) . ' versement(s))');
            } else {
                $skipped++;
                $this->warn("  ⚠ Événement #{$r['event_id']} non réglé : " . ($r['reason'] ?? 'inconnu'));
            }
        }

        $this->info("Terminé : {$settled} réglé(s), {$skipped} en attente (à vérifier côté admin).");

        return self::SUCCESS;
    }
}
