<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserType;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Réinitialisation admin d'un billet scanné -> de nouveau valide et scannable
 * (cas de fraude avérée). Réservé aux administrateurs.
 */
class ResetTicketScanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $type = UserType::create(['name' => 'admin', 'label' => 'Admin', 'slug' => 'admin-' . uniqid()]);
        $user = User::create([
            'name' => 'Super Admin', 'email' => 'admin-' . uniqid() . '@x.test',
            'password' => bcrypt('x'),
        ]);
        $user->user_type_id = $type->id; // non fillable
        $user->save();
        return $user;
    }

    private function plainUser(): User
    {
        return User::create([
            'name' => 'Client', 'email' => 'u-' . uniqid() . '@x.test', 'password' => bcrypt('x'),
        ]);
    }

    private function issuedTicket(): Ticket
    {
        $organizer = Organizer::create(['name' => 'Org', 'slug' => 'o-' . uniqid(), 'status' => 'active', 'is_active' => true]);
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'E', 'slug' => 'e-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
        return Ticket::create([
            'event_id' => $event->id, 'code' => 'TKT-RESET-1', 'status' => 'issued',
            'ticket_source' => 'online', 'issued_at' => now(),
        ]);
    }

    private function reset(User $user, string $code)
    {
        $req = Request::create('/x', 'POST');
        $req->setUserResolver(fn () => $user);
        return (new AdminController())->resetTicketScan($req, $code);
    }

    public function test_admin_can_reset_a_scanned_ticket_and_it_is_scannable_again(): void
    {
        $svc = app(TicketValidationService::class);
        $ticket = $this->issuedTicket();
        $scanner = $this->plainUser();

        // 1er scan -> valide
        $first = $svc->validate($ticket->code, ['scanned_by' => $scanner->id]);
        $this->assertSame('valid', $first['result']);
        $this->assertSame('used', $ticket->fresh()->status);

        // 2e scan -> doublon (anti double-scan)
        $dup = $svc->validate($ticket->code, ['scanned_by' => $scanner->id]);
        $this->assertSame('duplicate', $dup['result']);

        // Réinitialisation admin
        $res = $this->reset($this->admin(), $ticket->code);
        $data = json_decode($res->getContent(), true);
        $this->assertSame(200, $res->status());
        $this->assertTrue($data['success']);

        $fresh = $ticket->fresh();
        $this->assertSame('issued', $fresh->status);
        $this->assertNull($fresh->used_at);
        $this->assertSame(0, $fresh->checkins()->where('result', 'valid')->count());
        $this->assertSame(1, $fresh->checkins()->where('result', 'reset')->count());

        // Re-scan -> de nouveau valide
        $again = $svc->validate($ticket->code, ['scanned_by' => $scanner->id]);
        $this->assertSame('valid', $again['result']);
        $this->assertSame('used', $ticket->fresh()->status);
    }

    public function test_non_admin_cannot_reset(): void
    {
        $ticket = $this->issuedTicket();
        $res = $this->reset($this->plainUser(), $ticket->code);
        $this->assertSame(403, $res->status());
    }

    public function test_reset_unknown_ticket_is_404(): void
    {
        $res = $this->reset($this->admin(), 'NOPE');
        $this->assertSame(404, $res->status());
    }
}
