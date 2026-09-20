<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Rattrapage des affiches mises en ligne avant `ImageOptimizer`.
 *
 * Sans variante `medium_`, chaque carte tente la variante, prend un 404 et
 * recharge le master : une requête perdue et une image dix fois trop lourde par
 * événement. À lancer une fois après déploiement.
 */
class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize
        {--shrink : réécrit aussi les masters de plus de 1600 px (irréversible)}
        {--dry-run : liste ce qui serait fait, sans rien écrire}';

    protected $description = 'Génère les variantes manquantes des images déjà stockées';

    /** Dossiers du disque public susceptibles de contenir des images. */
    private const DIRECTORIES = ['images/events', 'events', 'venues', 'images/venues'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');
        $shrink = (bool) $this->option('shrink');

        $created = 0;
        $shrunk = 0;
        $oversized = 0;
        $failed = 0;

        foreach (self::DIRECTORIES as $directory) {
            if (! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->files($directory) as $path) {
                $name = basename($path);

                // Ne pas traiter les variantes elles-mêmes.
                if (str_starts_with($name, ImageOptimizer::MEDIUM_PREFIX)) {
                    continue;
                }

                if (! preg_match('/\.(jpe?g|png|gif|webp)$/i', $name)) {
                    continue;
                }

                try {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($disk->get($path));
                    $width = $image->width();
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    $mediumPath = ImageOptimizer::mediumPath($path);
                    if (! $disk->exists($mediumPath)) {
                        if (! $dryRun) {
                            $disk->put($mediumPath, $this->encode(
                                (clone $image)->scaleDown(width: ImageOptimizer::MEDIUM_WIDTH),
                                $extension
                            ));
                        }
                        $created++;
                    }

                    if ($width > ImageOptimizer::MAX_WIDTH) {
                        $oversized++;
                        if ($shrink) {
                            if (! $dryRun) {
                                $disk->put($path, $this->encode(
                                    (clone $image)->scaleDown(width: ImageOptimizer::MAX_WIDTH),
                                    $extension
                                ));
                            }
                            $shrunk++;
                        }
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->warn("  {$path} : {$e->getMessage()}");
                }
            }
        }

        $prefix = $dryRun ? '[simulation] ' : '';
        $this->info("{$prefix}Variantes créées : {$created}");
        $this->info("{$prefix}Masters trop grands : {$oversized}" . ($shrink ? " (réduits : {$shrunk})" : ' (relancer avec --shrink pour les réduire)'));

        if ($failed > 0) {
            $this->warn("Images illisibles ignorées : {$failed}");
        }

        return self::SUCCESS;
    }

    private function encode($image, string $extension): string
    {
        return match ($extension) {
            'png' => (string) $image->toPng(),
            'gif' => (string) $image->toGif(),
            'webp' => (string) $image->toWebp(82),
            default => (string) $image->toJpeg(82),
        };
    }
}
