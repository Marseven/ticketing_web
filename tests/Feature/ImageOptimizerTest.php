<?php

namespace Tests\Feature;

use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Optimisation des affiches à l'upload : une photo de téléphone ne doit pas
 * être servie en pleine résolution à chaque visiteur.
 */
class ImageOptimizerTest extends TestCase
{
    private function readImage(string $path)
    {
        return (new ImageManager(new Driver()))->read(Storage::disk('public')->get($path));
    }

    public function test_large_upload_is_scaled_down_and_gets_a_medium_variant(): void
    {
        Storage::fake('public');

        $path = (new ImageOptimizer())->store(
            UploadedFile::fake()->image('affiche.jpg', 4000, 3000),
            'images/events',
            'affiche.jpg'
        );

        $this->assertSame('images/events/affiche.jpg', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists('images/events/medium_affiche.jpg');

        $this->assertSame(ImageOptimizer::MAX_WIDTH, $this->readImage($path)->width());
        $this->assertSame(
            ImageOptimizer::MEDIUM_WIDTH,
            $this->readImage('images/events/medium_affiche.jpg')->width()
        );
    }

    public function test_small_image_is_not_upscaled(): void
    {
        Storage::fake('public');

        $path = (new ImageOptimizer())->store(
            UploadedFile::fake()->image('petite.jpg', 500, 400),
            'images/events',
            'petite.jpg'
        );

        $this->assertSame(500, $this->readImage($path)->width());
        $this->assertSame(500, $this->readImage('images/events/medium_petite.jpg')->width());
    }

    public function test_forget_removes_the_variant_too(): void
    {
        Storage::fake('public');

        $path = (new ImageOptimizer())->store(
            UploadedFile::fake()->image('vieille.jpg', 1200, 800),
            'images/events',
            'vieille.jpg'
        );

        ImageOptimizer::forget($path);

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing('images/events/medium_vieille.jpg');
    }

    public function test_medium_path_keeps_the_directory(): void
    {
        $this->assertSame('images/events/medium_a.jpg', ImageOptimizer::mediumPath('images/events/a.jpg'));
        $this->assertSame('medium_a.jpg', ImageOptimizer::mediumPath('a.jpg'));
    }
}
