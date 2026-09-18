<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Page publique de suivi des billets d'un événement, accessible par un JETON
 * non devinable (pas de login). Destinée aux organisateurs peu techniques :
 * stats de l'événement + liste complète des billets (scannés ou non) + détail
 * d'un billet (acheteur, date d'achat, qui a scanné et quand).
 *
 * ⚠️ Le jeton EST l'authentification : quiconque a le lien voit les données
 * personnelles des acheteurs. Le jeton est régénérable pour révoquer un lien.
 */
class EventTrackingController extends Controller
{
    private function resolveEvent(string $token): Event
    {
        return Event::with(['organizer', 'venue', 'schedules'])
            ->where('tracking_token', $token)
            ->firstOrFail();
    }

    /**
     * Résumé de l'événement + statistiques de billetterie / scan.
     */
    public function summary(string $token): JsonResponse
    {
        $event = $this->resolveEvent($token);

        $base = Ticket::where('event_id', $event->id);

        $byType = (clone $base)
            ->selectRaw('ticket_type_id,
                COUNT(*) as total,
                SUM(CASE WHEN status = \'used\' THEN 1 ELSE 0 END) as scanned')
            ->groupBy('ticket_type_id')
            ->with('ticketType:id,name')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->ticketType->name ?? 'Sans type',
                'total' => (int) $r->total,
                'scanned' => (int) $r->scanned,
            ])->values();

        $total = (clone $base)->count();
        $scanned = (clone $base)->where('status', 'used')->count();

        $revenue = Order::whereHas('tickets', fn ($q) => $q->where('event_id', $event->id))
            ->where('status', 'paid')
            ->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'event' => [
                    'title' => $event->title,
                    'image_url' => $event->image,
                    'organizer' => $event->organizer?->name,
                    'venue' => $event->venue?->name,
                    'date' => $event->schedules->first()?->starts_at,
                ],
                'stats' => [
                    'total' => $total,
                    'scanned' => $scanned,
                    'not_scanned' => $total - $scanned,
                    'physical' => (clone $base)->where('ticket_source', 'physical')->count(),
                    'online' => (clone $base)->where('ticket_source', 'online')->count(),
                    'comped' => (clone $base)->where('ticket_source', 'comped')->count(),
                    'revenue' => (float) $revenue,
                    'by_type' => $byType,
                ],
            ],
        ]);
    }

    /**
     * Liste paginée des billets avec recherche et filtres.
     */
    public function tickets(Request $request, string $token): JsonResponse
    {
        $event = $this->resolveEvent($token);

        $query = Ticket::where('event_id', $event->id)
            ->with([
                'ticketType:id,name',
                'order:id,reference,placed_at,guest_name,guest_email,guest_phone,buyer_id',
                'order.buyer:id,name,email,phone',
                'buyer:id,name,email,phone',
                'checkins' => fn ($q) => $q->where('result', 'valid')->latest('scanned_at')->with('scanner:id,name'),
            ]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhereHas('buyer', fn ($b) => $b->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
                  ->orWhereHas('order', fn ($o) => $o->where('reference', 'like', "%{$s}%")
                      ->orWhere('guest_name', 'like', "%{$s}%")
                      ->orWhere('guest_email', 'like', "%{$s}%"));
            });
        }

        // Filtre "scanné / non scanné"
        if ($request->filled('scan')) {
            if ($request->scan === 'scanned') {
                $query->where('status', 'used');
            } elseif ($request->scan === 'not_scanned') {
                $query->where('status', '!=', 'used');
            }
        }

        if ($request->filled('ticket_source')) {
            $query->where('ticket_source', $request->ticket_source);
        }

        $paginator = $query->orderByDesc('created_at')->paginate(30);

        $paginator->getCollection()->transform(function ($t) {
            $lastScan = $t->checkins->first();
            $holder = $t->buyer ?: $t->order?->buyer;

            return [
                'code' => $t->code,
                'type' => $t->ticketType?->name,
                'source' => $t->ticket_source,
                'status' => $t->status,
                'scanned' => $t->status === 'used',
                'scanned_at' => $t->used_at,
                'scanned_by' => $lastScan?->scanner?->name,
                'holder_name' => $holder?->name ?? $t->order?->guest_name,
                'holder_email' => $holder?->email ?? $t->order?->guest_email,
                'purchase_date' => $t->order?->placed_at,
                'order_reference' => $t->order?->reference,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['tickets' => $paginator],
        ]);
    }

    /**
     * Détail complet d'un billet : acheteur, commande, historique des scans.
     */
    public function ticket(string $token, string $code): JsonResponse
    {
        $event = $this->resolveEvent($token);

        $ticket = Ticket::where('event_id', $event->id)
            ->where('code', $code)
            ->with([
                'ticketType:id,name',
                'schedule:id,starts_at',
                'order:id,reference,placed_at,total_amount,currency,status,guest_name,guest_email,guest_phone,buyer_id',
                'order.buyer:id,name,email,phone',
                'buyer:id,name,email,phone',
                'checkins' => fn ($q) => $q->latest('scanned_at')->with('scanner:id,name'),
            ])
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Billet introuvable'], 404);
        }

        $holder = $ticket->buyer ?: $ticket->order?->buyer;

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => [
                    'code' => $ticket->code,
                    'type' => $ticket->ticketType?->name,
                    'source' => $ticket->ticket_source,
                    'status' => $ticket->status,
                    'scanned_at' => $ticket->used_at,
                    'schedule_date' => $ticket->schedule?->starts_at,
                    'holder' => [
                        'name' => $holder?->name ?? $ticket->order?->guest_name,
                        'email' => $holder?->email ?? $ticket->order?->guest_email,
                        'phone' => $holder?->phone ?? $ticket->order?->guest_phone,
                    ],
                    'order' => $ticket->order ? [
                        'reference' => $ticket->order->reference,
                        'purchase_date' => $ticket->order->placed_at,
                        'total_amount' => $ticket->order->total_amount,
                        'currency' => $ticket->order->currency,
                        'status' => $ticket->order->status,
                    ] : null,
                    'checkins' => $ticket->checkins->map(fn ($c) => [
                        'result' => $c->result,
                        'scanned_at' => $c->scanned_at,
                        'scanned_by' => $c->scanner?->name,
                        'device_id' => $c->device_id,
                    ])->values(),
                ],
            ],
        ]);
    }
}
