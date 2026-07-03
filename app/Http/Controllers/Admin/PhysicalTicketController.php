<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PhysicalTicketController extends Controller
{
    /**
     * Générer un lot de billets physiques (QR à imprimer) pour un événement.
     */
    public function generate(Request $request, $eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);

        $validated = $request->validate([
            'ticket_type_id' => 'nullable|integer|exists:ticket_types,id',
            'schedule_id' => 'nullable|integer|exists:event_schedules,id',
            'quantity' => 'required|integer|min:1|max:500',
        ]);

        // Valider l'appartenance à l'événement.
        if (!empty($validated['ticket_type_id'])
            && !$event->ticketTypes()->where('id', $validated['ticket_type_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Type de billet invalide pour cet événement'], 422);
        }
        if (!empty($validated['schedule_id'])
            && !$event->schedules()->where('id', $validated['schedule_id'])->exists()) {
            return response()->json(['success' => false, 'message' => 'Date invalide pour cet événement'], 422);
        }

        $batchReference = 'BATCH-' . strtoupper(Str::random(6));
        $created = [];

        DB::transaction(function () use ($event, $validated, $batchReference, &$created) {
            for ($i = 0; $i < $validated['quantity']; $i++) {
                $created[] = Ticket::create([
                    'order_id' => null,
                    'event_id' => $event->id,
                    'ticket_type_id' => $validated['ticket_type_id'] ?? null,
                    'schedule_id' => $validated['schedule_id'] ?? null,
                    'buyer_id' => null,
                    'code' => $this->generateUniqueCode(),
                    'status' => 'issued',
                    'ticket_source' => 'physical',
                    'batch_reference' => $batchReference,
                    'issued_at' => now(),
                ])->id;
            }
        });

        return response()->json([
            'success' => true,
            'message' => count($created) . ' billet(s) physique(s) généré(s)',
            'data' => [
                'batch_reference' => $batchReference,
                'count' => count($created),
                'print_url' => "/api/v1/admin/physical-tickets/batches/{$batchReference}/print",
            ],
        ], 201);
    }

    /**
     * Lister les lots de billets physiques d'un événement, avec suivi de scan.
     */
    public function batches(Request $request, $eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);

        $batches = Ticket::where('event_id', $event->id)
            ->where('ticket_source', 'physical')
            ->whereNotNull('batch_reference')
            ->selectRaw('batch_reference,
                COUNT(*) as total,
                SUM(CASE WHEN status = \'used\' THEN 1 ELSE 0 END) as scanned,
                MIN(created_at) as created_at')
            ->groupBy('batch_reference')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($b) => [
                'batch_reference' => $b->batch_reference,
                'total' => (int) $b->total,
                'scanned' => (int) $b->scanned,
                'not_scanned' => (int) $b->total - (int) $b->scanned,
                'created_at' => $b->created_at,
                'print_url' => "/api/v1/admin/physical-tickets/batches/{$b->batch_reference}/print",
            ]);

        // Synthèse par provenance (physique vs en ligne).
        $bySource = Ticket::where('event_id', $event->id)
            ->selectRaw('ticket_source,
                COUNT(*) as total,
                SUM(CASE WHEN status = \'used\' THEN 1 ELSE 0 END) as scanned')
            ->groupBy('ticket_source')
            ->get()
            ->keyBy('ticket_source');

        return response()->json([
            'success' => true,
            'data' => [
                'event' => ['id' => $event->id, 'title' => $event->title],
                'batches' => $batches,
                'summary' => [
                    'physical' => [
                        'total' => (int) ($bySource['physical']->total ?? 0),
                        'scanned' => (int) ($bySource['physical']->scanned ?? 0),
                    ],
                    'online' => [
                        'total' => (int) ($bySource['online']->total ?? 0),
                        'scanned' => (int) ($bySource['online']->scanned ?? 0),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Générer le PDF imprimable d'un lot (un QR par billet).
     */
    public function printBatch(string $batchReference)
    {
        $tickets = Ticket::with(['event', 'ticketType', 'schedule'])
            ->where('batch_reference', $batchReference)
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            abort(404, 'Lot introuvable');
        }

        $items = $tickets->map(function (Ticket $ticket) {
            // Format SVG : backend par défaut (pas de dépendance imagick), rendu
            // par dompdf via data URI.
            $svg = QrCode::format('svg')->size(220)->margin(1)->generate($ticket->code);
            return [
                'code' => $ticket->code,
                'qr' => 'data:image/svg+xml;base64,' . base64_encode($svg),
                'event_title' => $ticket->event->title ?? '',
                'type' => $ticket->ticketType->name ?? null,
                'date' => $ticket->schedule?->starts_at,
            ];
        });

        $pdf = Pdf::loadView('pdf.physical-tickets', [
            'items' => $items,
            'batch' => $batchReference,
        ])->setPaper('a4');

        return $pdf->download("tickets-{$batchReference}.pdf");
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (Ticket::where('code', $code)->exists());

        return $code;
    }
}
