<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PhysicalTicketController;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Impression PDF d'un lot de billets physiques : le nouveau gabarit (affiche +
 * QR + réf + « ne pas partager ») doit se rendre sans erreur.
 */
class PhysicalTicketPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_batch_renders_a_pdf(): void
    {
        $organizer = Organizer::create(['name' => 'Org', 'slug' => 'o-' . uniqid(), 'status' => 'active', 'is_active' => true]);
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'MADE IN GABAO', 'slug' => 'e-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
        Ticket::create([
            'event_id' => $event->id, 'code' => 'TKT-PHYS-1', 'status' => 'issued',
            'ticket_source' => 'physical', 'batch_reference' => 'BATCH-XYZ', 'issued_at' => now(),
        ]);
        Ticket::create([
            'event_id' => $event->id, 'code' => 'TKT-PHYS-2', 'status' => 'issued',
            'ticket_source' => 'physical', 'batch_reference' => 'BATCH-XYZ', 'issued_at' => now(),
        ]);

        $response = (new PhysicalTicketController())->printBatch('BATCH-XYZ');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF', substr($response->getContent(), 0, 4));
    }
}
