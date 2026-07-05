<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Exécute l'import legacy MyTicketO en arrière-plan (gros volumes → pas de
 * timeout web). La progression/état est publiée dans le cache (partagé via le
 * cache base de données), lu par LegacyImportController::status().
 */
class RunLegacyImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const CACHE_KEY = 'legacy_import_status';

    public int $timeout = 1800; // 30 min

    public function __construct(public bool $fresh, public bool $all)
    {
    }

    public function handle(): void
    {
        Cache::put(self::CACHE_KEY, [
            'state' => 'running',
            'started_at' => now()->toDateTimeString(),
        ], 3600);

        try {
            $params = [];
            if ($this->fresh) {
                $params['--fresh'] = true;
            }
            if ($this->all) {
                $params['--all'] = true;
            }

            Artisan::call('legacy:import', $params);

            Cache::put(self::CACHE_KEY, [
                'state' => 'done',
                'output' => Artisan::output(),
                'finished_at' => now()->toDateTimeString(),
            ], 3600);
        } catch (\Throwable $e) {
            Cache::put(self::CACHE_KEY, [
                'state' => 'error',
                'message' => $e->getMessage(),
                'finished_at' => now()->toDateTimeString(),
            ], 3600);
        }
    }

    public function failed(\Throwable $e): void
    {
        Cache::put(self::CACHE_KEY, [
            'state' => 'error',
            'message' => $e->getMessage(),
            'finished_at' => now()->toDateTimeString(),
        ], 3600);
    }
}
