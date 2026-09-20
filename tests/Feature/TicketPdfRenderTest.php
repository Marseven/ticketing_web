<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TicketController;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PDF d'un billet (/tickets/{code}/pdf) : le gabarit aligné sur le billet web
 * (affiche + QR, mention noire, logo) doit se rendre sans erreur.
 */
class TicketPdfRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_pdf_renders(): void
    {
        $organizer = Organizer::create(['name' => 'Org', 'slug' => 'o-' . uniqid(), 'status' => 'active', 'is_active' => true]);
        $event = Event::create([
            'organizer_id' => $organizer->id, 'title' => 'CHILL EXPO', 'slug' => 'e-' . uniqid(),
            'description' => 'x', 'status' => 'published', 'approval_status' => 'approved',
        ]);
        Ticket::create([
            'event_id' => $event->id, 'code' => 'TKT-PDF-1', 'status' => 'issued',
            'ticket_source' => 'online', 'issued_at' => now(),
        ]);

        $response = (new TicketController())->downloadPDF('TKT-PDF-1');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF', substr($response->getContent(), 0, 4));
    }
}
