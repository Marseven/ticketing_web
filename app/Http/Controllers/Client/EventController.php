<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request)
    {
        $query = Event::where('status', 'published')
            ->with(['organizer', 'schedules', 'ticketTypes', 'venue', 'category']);

        // Recherche par mot-clé
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('venue', function ($venueQuery) use ($search) {
                      $venueQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrage par catégorie
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filtrage par date
        if ($request->filled('date')) {
            $query->whereHas('schedules', function ($q) use ($request) {
                $q->whereDate('starts_at', $request->date);
            });
        }

        // Filtrage par ville
        if ($request->filled('city')) {
            $query->whereHas('venue', function ($venueQuery) use ($request) {
                $venueQuery->where('city', 'like', "%{$request->city}%");
            });
        }

        $events = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Récupération des catégories
        $categories = \App\Models\EventCategory::select('id', 'name', 'slug')
            ->whereHas('events', function($q) {
                $q->where('status', 'published');
            })
            ->get()
            ->toArray();

        // Si c'est une requête API (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            // Enrichir les données pour le frontend
            $enrichedEvents = collect($events->items())->map(function($event) {
                // Ajouter un prix fictif aux ticket types pour l'affichage
                if ($event->ticketTypes) {
                    $event->ticketTypes = $event->ticketTypes->map(function($ticketType) {
                        $ticketType->price = 10000; // Prix par défaut pour l'affichage
                        return $ticketType;
                    });
                }
                return $event;
            });

            return response()->json([
                'success' => true,
                'events' => $enrichedEvents,
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'categories' => $categories,
            ]);
        }

        return view('client.events.index', compact('events', 'categories'));
    }

    /**
     * Display the specified event.
     */
    public function show(Request $request, Event $event)
    {
        // Vérifier que l'événement est publié
        if ($event->status !== 'published' || !$event->is_active) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Événement non trouvé'
                ], 404);
            }
            abort(404, 'Événement non trouvé');
        }

        $event->load([
            'organizer',
            'venue',
            'category',
            'schedules' => function ($query) {
                $query->orderBy('starts_at');
            },
            'ticketTypes'
        ]);

        // Événements similaires basés sur la ville, catégorie ou l'organisateur
        $relatedEvents = Event::where('status', 'published')
            ->where('id', '!=', $event->id)
            ->where(function ($query) use ($event) {
                $query->where('category_id', $event->category_id)
                      ->orWhere('organizer_id', $event->organizer_id)
                      ->orWhere('venue_id', $event->venue_id);
            })
            ->with(['venue', 'category', 'schedules'])
            ->limit(4)
            ->get();

        // Si c'est une requête API (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'event' => $event,
                'related_events' => $relatedEvents,
            ]);
        }

        return view('client.events.show', compact('event', 'relatedEvents'));
    }
}