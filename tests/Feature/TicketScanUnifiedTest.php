<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validation UNIFIÉE du scan (service commun) : numérique + physique passent
 * par la même logique, et un billet physique sans type/acheteur/ordre ne fait
 * plus planter le scan (fix null-safety).
 */
class TicketScanUnifiedTest extends TestCase
{
    use RefreshDatabase;

    private function scannerId(): int
    {
        return User::create([
            'name' => 'Scanner', 'email' => 'scan-' . uniqid() . '@x.test',
            'password' => bcrypt('secret'),
        ])->id;
    }

    private function makeEvent(): Event
    {
        $organizer = Organizer::create([
            'name' => 'Org', 'slug' => 'org-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert', 'slug' => 'concert-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
    }

    public function test_physical_ticket_without_type_scans_without_crashing(): void
    {
        $event = $this->makeEvent();
        $ticket = Ticket::create([
            'order_id' => null, 'event_id' => $event->id, 'buyer_id' => null,
            'ticket_type_id' => null, 'schedule_id' => null,
            'code' => 'PHY-NOTYPE-1', 'status' => 'issued',
            'ticket_source' => 'physical', 'issued_at' => now(),
        ]);

        $r = app(TicketValidationService::class)->validate('PHY-NOTYPE-1', ['scanned_by' => $this->scannerId()]);

        $this->assertSame('valid', $r['result']);
        $this->assertTrue($r['valid']);
        $this->assertSame('physical', $r['source']);
        $this->assertNull($r['ticket']['ticket_type']); // null-safe
        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertSame(1, $ticket->checkins()->where('result', 'valid')->count());
    }

    public function test_second_scan_is_duplicate(): void
    {
        $event = $this->makeEvent();
        Ticket::create([
            'event_id' => $event->id, 'code' => 'DUP-1', 'status' => 'issued',
            'ticket_source' => 'physical', 'issued_at' => now(),
        ]);

        $uid = $this->scannerId();
        $svc = app(TicketValidationService::class);
        $svc->validate('DUP-1', ['scanned_by' => $uid]);
        $second = $svc->validate('DUP-1', ['scanned_by' => $uid]);

        $this->assertSame('duplicate', $second['result']);
        $this->assertFalse($second['valid']);
        $this->assertNotNull($second['first_scan']);
    }

    public function test_unknown_code_is_not_found(): void
    {
        $r = app(TicketValidationService::class)->validate('DOES-NOT-EXIST');

        $this->assertSame('not_found', $r['result']);
        $this->assertFalse($r['valid']);
        $this->assertNull($r['ticket']);
    }

    public function test_digital_ticket_with_type_scans(): void
    {
        $event = $this->makeEvent();
        $type = TicketType::create([
            'event_id' => $event->id, 'name' => 'VIP', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active',
        ]);
        Ticket::create([
            'event_id' => $event->id, 'ticket_type_id' => $type->id,
            'code' => 'DIG-1', 'status' => 'issued',
            'ticket_source' => 'online', 'issued_at' => now(),
        ]);

        $r = app(TicketValidationService::class)->validate('DIG-1', ['scanned_by' => $this->scannerId()]);

        $this->assertSame('valid', $r['result']);
        $this->assertSame('online', $r['source']);
        $this->assertSame('VIP', $r['ticket']['ticket_type']['name']);
    }
}
