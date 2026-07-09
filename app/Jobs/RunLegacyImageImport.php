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
 * Copie/télécharge les images des événements legacy MyTicketO dans le stockage
 * Primea, en arrière-plan (téléchargement de ~100 fichiers → pas de timeout web).
 * État publié dans le cache FICHIER (hors transaction, lisible en direct).
 */
class RunLegacyImageImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const CACHE_KEY = 'legacy_image_import_status';

    public int $timeout = 1800; // 30 min

    public function __construct(public ?string $source = null, public bool $force = false)
    {
    }

    public static function setStatus(array $data, int $ttl = 3600): void
    {
        Cache::store('file')->put(self::CACHE_KEY, $data, $ttl);
    }

    public static function getStatus(): ?array
    {
        return Cache::store('file')->get(self::CACHE_KEY);
    }

    public function handle(): void
    {
        self::setStatus([
            'state' => 'running',
            'step' => 'Démarrage…',
            'started_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        try {
            $params = [];
            if ($this->source) {
                $params['--source'] = $this->source;
            }
            if ($this->force) {
                $params['--force'] = true;
            }

            Artisan::call('legacy:import-images', $params);

            // La commande a déjà publié un état 'done' détaillé.
            $status = self::getStatus() ?? [];
            self::setStatus(array_merge($status, [
                'state' => 'done',
                'output' => Artisan::output(),
                'finished_at' => now()->toDateTimeString(),
            ]));
        } catch (\Throwable $e) {
            self::setStatus([
                'state' => 'error',
                'message' => $e->getMessage(),
                'finished_at' => now()->toDateTimeString(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        self::setStatus([
            'state' => 'error',
            'message' => $e->getMessage(),
            'finished_at' => now()->toDateTimeString(),
        ]);
    }
}
