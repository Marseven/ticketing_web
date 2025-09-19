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
            ->where('is_active', true)
            ->with([
                'organizer:id,name,slug',
                'schedules' => function($q) {
                    $q->where('status', 'active')->orderBy('starts_at');
                },
                'ticketTypes' => function($q) {
                    $q->where('status', 'active')->orderBy('price');
                },
                'venue:id,name,city,address'
            ]);

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


        // Si c'est une requête API (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            // Enrichir les données pour le frontend
            $enrichedEvents = collect($events->items())->map(function($event) {
                // Créer un tableau vide au lieu d'utiliser toArray() qui peut avoir des problèmes avec les accessors
                $eventArray = [
                    'id' => $event->id,
                    'organizer_id' => $event->organizer_id,
                    'category_id' => $event->category_id,
                    'venue_id' => $event->venue_id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'description' => $event->description,
                    'image_url' => $event->image_url,
                    'status' => $event->status,
                    'is_active' => $event->is_active,
                    'published_at' => $event->published_at,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'organizer' => $event->organizer ? [
                        'id' => $event->organizer->id,
                        'name' => $event->organizer->name,
                        'slug' => $event->organizer->slug,
                    ] : null,
                    'venue' => $event->venue ? [
                        'id' => $event->venue->id,
                        'name' => $event->venue->name,
                        'city' => $event->venue->city,
                        'address' => $event->venue->address,
                    ] : null,
                ];
                
                // Ajouter des données calculées pour l'affichage
                if ($event->ticketTypes && $event->ticketTypes->count() > 0) {
                    $ticketTypes = $event->ticketTypes->map(function($ticketType) {
                        // Calculer sold_quantity directement sans utiliser l'accessor
                        $soldQuantity = $ticketType->tickets()->whereIn('status', ['issued', 'used'])->count();
                        $remainingQuantity = $ticketType->available_quantity ? 
                            max(0, $ticketType->available_quantity - $soldQuantity) : null;
                            
                        return [
                            'id' => $ticketType->id,
                            'name' => $ticketType->name,
                            'description' => $ticketType->description,
                            'price' => (float) $ticketType->price,
                            'currency' => $ticketType->currency ?? 'XOF',
                            'available_quantity' => $ticketType->available_quantity,
                            'sold_quantity' => $soldQuantity,
                            'remaining_quantity' => $remainingQuantity,
                            'is_available' => $ticketType->isAvailable(),
                            'status' => $ticketType->status,
                        ];
                    });
                    $eventArray['ticket_types'] = $ticketTypes;
                    
                    // Calculer min et max prix correctement
                    $prices = $ticketTypes->pluck('price')->filter(function($price) {
                        return $price > 0;
                    });
                    
                    $eventArray['min_price'] = $prices->count() > 0 ? $prices->min() : 0;
                    $eventArray['max_price'] = $prices->count() > 0 ? $prices->max() : 0;
                } else {
                    $eventArray['ticket_types'] = [];
                    $eventArray['min_price'] = 0;
                    $eventArray['max_price'] = 0;
                }

                // S'assurer que les dates sont au bon format
                if ($event->schedules && $event->schedules->count() > 0) {
                    $schedules = $event->schedules->map(function($schedule) {
                        return [
                            'id' => $schedule->id,
                            'starts_at' => $schedule->starts_at->toISOString(),
                            'ends_at' => $schedule->ends_at->toISOString(),
                            'door_time' => $schedule->door_time ? $schedule->door_time->toISOString() : null,
                            'status' => $schedule->status,
                        ];
                    });
                    $eventArray['schedules'] = $schedules;
                    $eventArray['next_schedule'] = $schedules->first();
                } else {
                    $eventArray['schedules'] = [];
                    $eventArray['next_schedule'] = null;
                }

                // Ajouter des informations de venue formatées
                if ($event->venue) {
                    $eventArray['venue_name'] = $event->venue->name;
                    $eventArray['venue_city'] = $event->venue->city;
                    $eventArray['venue_address'] = $event->venue->address;
                }

                // Ajouter les informations de catégorie depuis event_categories
                $category = \DB::table('event_categories')->where('id', $event->category_id)->first();
                if ($category) {
                    $eventArray['category'] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug
                    ];
                    $eventArray['category_name'] = $category->name;
                }

                // Marquer comme featured pour certains événements (logique à définir)
                $eventArray['is_featured'] = $event->id % 3 === 0; // Exemple: chaque 3e événement

                return $eventArray;
            });

            return response()->json([
                'success' => true,
                'events' => $enrichedEvents,
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ]);
        }

        return view('client.events.index', compact('events'));
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