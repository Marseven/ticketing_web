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

    /**
     * L'état d'import est stocké dans le cache FICHIER (pas le cache base de
     * données) : la commande écrit sa progression PENDANT une grosse
     * transaction DB — un état en base ne serait visible qu'après le commit.
     * Le store fichier est hors transaction, donc lisible en direct par le
     * polling web.
     */
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
            if ($this->fresh) {
                $params['--fresh'] = true;
            }
            if ($this->all) {
                $params['--all'] = true;
            }

            Artisan::call('legacy:import', $params);

            // La commande a déjà publié un état 'done' détaillé (compteurs) ;
            // on le conserve en y ajoutant le journal texte.
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
