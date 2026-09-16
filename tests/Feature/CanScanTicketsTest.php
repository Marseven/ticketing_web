<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Autorisation de scan : admin (tous events), organisateur, ou utilisateur
 * rattaché à un organisateur (events de son orga). Sinon interdit.
 */
class CanScanTicketsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $isOrganizer = false): User
    {
        return User::create([
            'name' => 'U ' . uniqid(),
            'email' => uniqid() . '@test.local',
            'password' => Hash::make('secret123'),
            'is_organizer' => $isOrganizer,
            'status' => 'active',
        ]);
    }

    private function makeOrganizer(): Organizer
    {
        return Organizer::create([
            'name' => 'Org ' . uniqid(),
            'slug' => 'org-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_organizer_flag_user_can_scan(): void
    {
        $this->assertTrue($this->makeUser(isOrganizer: true)->canScanTickets());
    }

    public function test_user_linked_to_organizer_can_scan_even_without_flag(): void
    {
        $user = $this->makeUser(isOrganizer: false);
        $user->organizers()->attach($this->makeOrganizer()->id, ['role' => 'scanner']);

        $this->assertTrue($user->fresh()->canScanTickets());
    }

    public function test_plain_user_cannot_scan(): void
    {
        $this->assertFalse($this->makeUser(isOrganizer: false)->canScanTickets());
    }
}
