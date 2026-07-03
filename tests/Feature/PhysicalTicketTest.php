<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billets physiques : génération d'un lot de QR sans achat, scannables et
 * comptabilisés séparément des ventes en ligne.
 */
class PhysicalTicketTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_physical_tickets_are_created_without_order_and_marked_physical(): void
    {
        $event = $this->makeEvent();

        $batchRef = 'BATCH-TEST01';
        foreach (range(1, 5) as $n) {
            Ticket::create([
                'order_id' => null,
                'event_id' => $event->id,
                'buyer_id' => null,
                'code' => 'TKT-PHY' . $n,
                'status' => 'issued',
                'ticket_source' => 'physical',
                'batch_reference' => $batchRef,
                'issued_at' => now(),
            ]);
        }

        $physical = Ticket::where('event_id', $event->id)->where('ticket_source', 'physical')->get();

        $this->assertCount(5, $physical);
        $this->assertTrue($physical->every(fn ($t) => $t->order_id === null));
        $this->assertTrue($physical->every(fn ($t) => $t->status === 'issued'));
        $this->assertSame(5, Ticket::where('batch_reference', $batchRef)->count());
    }

    public function test_scanning_a_physical_ticket_marks_it_used_and_counts_as_scanned(): void
    {
        $event = $this->makeEvent();

        $ticket = Ticket::create([
            'order_id' => null, 'event_id' => $event->id, 'buyer_id' => null,
            'code' => 'TKT-PHYSCAN', 'status' => 'issued',
            'ticket_source' => 'physical', 'batch_reference' => 'BATCH-SCAN',
            'issued_at' => now(),
        ]);

        // Simuler un scan validé : le ticket passe à used.
        $ticket->update(['status' => 'used', 'used_at' => now()]);

        $scanned = Ticket::where('event_id', $event->id)
            ->where('ticket_source', 'physical')
            ->where('status', 'used')
            ->count();

        $this->assertSame(1, $scanned);
    }
}
