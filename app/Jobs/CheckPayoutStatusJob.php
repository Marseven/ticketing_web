<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPayoutStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Backoff escalation (in seconds) between status polls when the payout is
     * still pending or processing at SHAP. Maps onto {@see $attempts}.
     */
    private const ATTEMPT_DELAYS = [
        1 => 300,    // 5 min
        2 => 600,    // 10 min
        3 => 1800,   // 30 min
        4 => 3600,   // 1 h
        5 => 7200,   // 2 h
    ];

    public function __construct(
        public int $payoutId,
        public int $attempt = 1,
    ) {
    }

    public function handle(PayoutService $payoutService): void
    {
        $payout = Payout::find($this->payoutId);

        if (!$payout) {
            Log::warning('CheckPayoutStatusJob: payout introuvable', ['payout_id' => $this->payoutId]);
            return;
        }

        if (in_array($payout->status, ['success', 'failed', 'cancelled'], true)) {
            Log::info('CheckPayoutStatusJob: statut déjà finalisé, arrêt du polling', [
                'payout_id' => $payout->id,
                'status' => $payout->status,
                'attempt' => $this->attempt,
            ]);
            return;
        }

        Log::info('CheckPayoutStatusJob: vérification statut SHAP', [
            'payout_id' => $payout->id,
            'attempt' => $this->attempt,
            'current_status' => $payout->status,
        ]);

        $result = $payoutService->checkPayoutStatus($payout);
        $payout->refresh();

        if (in_array($payout->status, ['success', 'failed', 'cancelled'], true)) {
            Log::info('CheckPayoutStatusJob: statut finalisé après vérification', [
                'payout_id' => $payout->id,
                'final_status' => $payout->status,
                'attempt' => $this->attempt,
            ]);
            return;
        }

        $nextAttempt = $this->attempt + 1;
        $delay = self::ATTEMPT_DELAYS[$nextAttempt] ?? null;

        if ($delay === null) {
            Log::error('CheckPayoutStatusJob: nombre max de tentatives atteint, abandon', [
                'payout_id' => $payout->id,
                'final_status' => $payout->status,
                'last_check' => $result,
            ]);
            return;
        }

        self::dispatch($payout->id, $nextAttempt)->delay(now()->addSeconds($delay));
    }
}
