<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index']);
    }

    /**
     * Display tickets for an event (public).
     */
    public function index(Event $event)
    {
        // Vérifier que l'événement est publié
        if ($event->status !== 'published' || !$event->is_active) {
            abort(404, 'Événement non trouvé');
        }

        $event->load([
            'schedules' => function ($query) {
                $query->orderBy('starts_at');
            },
            'ticketTypes' => function ($query) {
                $query->where('is_active', true)->orderBy('price');
            }
        ]);

        return view('client.events.tickets', compact('event'));
    }

    /**
     * Display user's tickets.
     */
    public function myTickets(Request $request)
    {
        $query = Auth::user()->tickets()
            ->with(['event', 'schedule', 'ticketType', 'order', 'checkins']);

        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrage par événement
        if ($request->filled('event')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->event}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $statuses = Ticket::select('status')
            ->distinct()
            ->pluck('status')
            ->toArray();

        return view('client.tickets.index', compact('tickets', 'statuses'));
    }
}