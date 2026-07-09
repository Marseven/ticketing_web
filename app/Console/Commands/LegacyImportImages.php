<?php

namespace App\Console\Commands;

use App\Jobs\RunLegacyImageImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Récupère les images des événements importés depuis MyTicketO et les place
 * dans le stockage Primea (storage/app/public/images/events), où l'accessor
 * Event::image les attend. Idempotent : saute les fichiers déjà présents.
 *
 * Deux sources, dans l'ordre :
 *   1. --source=<dir> : dossier local (ex : public_html/images du legacy).
 *   2. --base-url     : téléchargement HTTP (défaut : site MyTicketO en ligne).
 */
class LegacyImportImages extends Command
{
    protected $signature = 'legacy:import-images
        {--source= : Dossier local source (prioritaire sur l\'URL)}
        {--base-url=https://myticket-o.net/assets/images/event : URL de base pour télécharger les images manquantes}
        {--force : Re-copier/télécharger même si le fichier existe déjà}';

    protected $description = 'Copier/télécharger les images des événements legacy MyTicketO dans le stockage Primea';

    public function handle(): int
    {
        $source = $this->option('source') ? rtrim($this->option('source'), '/') : null;
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $force = (bool) $this->option('force');

        $targetDir = storage_path('app/public/images/events');
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            $this->error("Impossible de créer le dossier cible : {$targetDir}");
            RunLegacyImageImport::setStatus([
                'state' => 'error', 'message' => "Dossier cible non créable : {$targetDir}",
                'finished_at' => now()->toDateTimeString(),
            ]);
            return self::FAILURE;
        }

        $events = DB::table('events')
            ->whereNotNull('image_file')->where('image_file', '<>', '')
            ->get(['id', 'image_file']);

        $total = $events->count();
        $this->info("Images à traiter : {$total}");

        $counts = ['copied' => 0, 'downloaded' => 0, 'skipped' => 0, 'missing' => 0];
        $missing = [];
        $i = 0;

        RunLegacyImageImport::setStatus([
            'state' => 'running', 'step' => 'Images', 'item_done' => 0, 'item_total' => $total,
            'counts' => $counts, 'started_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        foreach ($events as $ev) {
            $i++;
            $file = basename((string) $ev->image_file); // anti path-traversal
            if ($file === '') {
                continue;
            }
            $target = $targetDir . '/' . $file;

            if (!$force && is_file($target) && filesize($target) > 0) {
                $counts['skipped']++;
            } else {
                $ok = false;

                // 1) source locale
                if ($source && is_file($source . '/' . $file)) {
                    $ok = @copy($source . '/' . $file, $target);
                    if ($ok) {
                        $counts['copied']++;
                    }
                }

                // 2) téléchargement HTTP
                if (!$ok && $baseUrl !== '') {
                    try {
                        $resp = Http::timeout(20)->retry(1, 200)->get($baseUrl . '/' . rawurlencode($file));
                        if ($resp->successful() && strlen($resp->body()) > 0) {
                            file_put_contents($target, $resp->body());
                            $ok = true;
                            $counts['downloaded']++;
                        }
                    } catch (\Throwable $e) {
                        // ignoré : compté comme manquant ci-dessous
                    }
                }

                if (!$ok) {
                    $counts['missing']++;
                    if (count($missing) < 50) {
                        $missing[] = $file;
                    }
                }
            }

            if ($i % 5 === 0 || $i === $total) {
                RunLegacyImageImport::setStatus([
                    'state' => 'running', 'step' => 'Images', 'item_done' => $i, 'item_total' => $total,
                    'counts' => $counts, 'updated_at' => now()->toDateTimeString(),
                ]);
            }
        }

        RunLegacyImageImport::setStatus([
            'state' => 'done', 'step' => 'Images copiées.', 'item_done' => $total, 'item_total' => $total,
            'counts' => $counts, 'missing' => $missing, 'finished_at' => now()->toDateTimeString(),
        ]);

        $this->info(sprintf(
            'Terminé : %d copiées, %d téléchargées, %d ignorées (déjà là), %d introuvables.',
            $counts['copied'], $counts['downloaded'], $counts['skipped'], $counts['missing']
        ));
        if ($missing) {
            $this->warn('Introuvables : ' . implode(', ', $missing));
        }

        return self::SUCCESS;
    }
}
