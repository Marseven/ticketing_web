<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Réduit une image à l'upload.
 *
 * Les affiches sont envoyées depuis un téléphone : 3 à 5 Mo, 4000 px de large,
 * pour être affichées dans une carte de 400 px. Stockées telles quelles, elles
 * étaient servies telles quelles — une liste de dix événements pouvait peser
 * des dizaines de mégaoctets sur un forfait mobile gabonais.
 *
 * On écrit donc deux fichiers : le master (borné à 1600 px, suffisant pour la
 * fiche et les écrans denses) et une variante `medium_` (800 px) pour les
 * listes. En cas d'échec (GD absent, fichier exotique), on retombe sur un
 * stockage brut : un upload ne doit jamais échouer à cause de l'optimisation.
 */
class ImageOptimizer
{
    /** Largeur maximale du fichier principal. */
    public const MAX_WIDTH = 1600;

    /** Largeur de la variante servie dans les listes. */
    public const MEDIUM_WIDTH = 800;

    /** Préfixe de la variante, aligné sur ImageController. */
    public const MEDIUM_PREFIX = 'medium_';

    private const QUALITY = 82;

    /**
     * Stocke l'image optimisée et sa variante.
     *
     * @param  string  $directory  dossier relatif au disque public, ex. `images/events`
     * @return string le chemin relatif du fichier principal (`images/events/xxx.jpg`)
     */
    public function store(UploadedFile $file, string $directory, string $filename, string $disk = 'public'): string
    {
        $path = trim($directory, '/') . '/' . $filename;

        try {
            $image = (new ImageManager(new Driver()))->read($file->getRealPath());

            // `scaleDown` ne fait rien si l'image est déjà plus petite : on ne
            // ré-upscale jamais une petite affiche.
            $master = (clone $image)->scaleDown(width: self::MAX_WIDTH);
            Storage::disk($disk)->put($path, $this->encode($master, $file));

            $medium = (clone $image)->scaleDown(width: self::MEDIUM_WIDTH);
            Storage::disk($disk)->put(
                $this->mediumPath($path),
                $this->encode($medium, $file)
            );
        } catch (\Throwable $e) {
            Log::warning('Optimisation d\'image impossible, stockage brut', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            $file->storeAs(trim($directory, '/'), $filename, $disk);
        }

        return $path;
    }

    /**
     * Chemin de la variante `medium` d'un fichier donné.
     */
    public static function mediumPath(string $path): string
    {
        $directory = trim(dirname($path), '.');
        $name = basename($path);

        return ($directory !== '' ? $directory . '/' : '') . self::MEDIUM_PREFIX . $name;
    }

    /**
     * Supprime un fichier et sa variante.
     */
    public static function forget(string $path, string $disk = 'public'): void
    {
        Storage::disk($disk)->delete([$path, self::mediumPath($path)]);
    }

    /**
     * Conserve le format d'origine (le PNG garde sa transparence), en JPEG
     * pour tout le reste — c'est le cas courant des photos.
     */
    private function encode($image, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'png' => (string) $image->toPng(),
            'gif' => (string) $image->toGif(),
            'webp' => (string) $image->toWebp(self::QUALITY),
            default => (string) $image->toJpeg(self::QUALITY),
        };
    }
}
