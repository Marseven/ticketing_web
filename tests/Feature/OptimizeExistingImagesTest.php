<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

/**
 * Rattrapage des affiches déjà en ligne : sans variante, chaque carte paie un
 * 404 avant de recharger le master.
 */
class OptimizeExistingImagesTest extends TestCase
{
    private function putImage(string $path, int $width, int $height): void
    {
        $image = (new ImageManager(new Driver()))->create($width, $height);

        Storage::disk('public')->put($path, (string) $image->toJpeg());
    }

    private function width(string $path): int
    {
        return (new ImageManager(new Driver()))->read(Storage::disk('public')->get($path))->width();
    }

    public function test_it_creates_the_missing_variant(): void
    {
        Storage::fake('public');
        $this->putImage('images/events/ancienne.jpg', 2400, 1600);

        $this->artisan('images:optimize')->assertSuccessful();

        Storage::disk('public')->assertExists('images/events/medium_ancienne.jpg');
        $this->assertSame(800, $this->width('images/events/medium_ancienne.jpg'));
        $this->assertSame(2400, $this->width('images/events/ancienne.jpg'), 'le master reste intact sans --shrink');
    }

    public function test_shrink_rewrites_oversized_masters(): void
    {
        Storage::fake('public');
        $this->putImage('images/events/enorme.jpg', 4000, 3000);

        $this->artisan('images:optimize', ['--shrink' => true])->assertSuccessful();

        $this->assertSame(1600, $this->width('images/events/enorme.jpg'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        Storage::fake('public');
        $this->putImage('images/events/intacte.jpg', 2000, 1500);

        $this->artisan('images:optimize', ['--dry-run' => true, '--shrink' => true])->assertSuccessful();

        Storage::disk('public')->assertMissing('images/events/medium_intacte.jpg');
        $this->assertSame(2000, $this->width('images/events/intacte.jpg'));
    }

    public function test_existing_variants_are_left_alone(): void
    {
        Storage::fake('public');
        $this->putImage('images/events/deja.jpg', 1200, 800);
        $this->putImage('images/events/medium_deja.jpg', 800, 533);

        $this->artisan('images:optimize')->assertSuccessful();

        // La variante n'est ni régénérée ni traitée comme un master.
        Storage::disk('public')->assertMissing('images/events/medium_medium_deja.jpg');
        $this->assertSame(800, $this->width('images/events/medium_deja.jpg'));
    }
}
