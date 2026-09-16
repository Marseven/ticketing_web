<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\CodOnScannerSeeder;
use Database\Seeders\MadeInGabaoEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CodOnScannerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_scanner_account_linked_to_codon(): void
    {
        $this->seed(MadeInGabaoEventSeeder::class);
        $this->seed(CodOnScannerSeeder::class);

        $user = User::where('email', 'scan@codon.ga')->first();
        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->is_organizer);
        $this->assertTrue(Hash::check('Codon@Scan2026', $user->password));

        // Lié à Cod'On → peut scanner ses events (ScanController: enforce_organizer)
        $event = Event::where('slug', 'made-in-gabao-session-28')->first();
        $organizerIds = $user->organizers->pluck('id');
        $this->assertTrue($organizerIds->contains($event->organizer_id));
    }

    public function test_is_idempotent(): void
    {
        $this->seed(MadeInGabaoEventSeeder::class);
        $this->seed(CodOnScannerSeeder::class);
        $this->seed(CodOnScannerSeeder::class);

        $this->assertSame(1, User::where('email', 'scan@codon.ga')->count());
        $user = User::where('email', 'scan@codon.ga')->first();
        $this->assertSame(1, $user->organizers()->count());
    }
}
