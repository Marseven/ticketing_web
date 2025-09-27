<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        // Si c'est une requête admin, récupérer toutes les catégories
        if ($request->user() && $request->user()->hasRole(\App\Models\Role::ADMIN)) {
            $categories = \App\Models\Category::orderBy('name')->get();
        } else {
            // Récupération des catégories qui ont des événements publiés
            $categories = \DB::table('event_categories')
                ->select('id', 'name', 'slug')
                ->whereExists(function($query) {
                    $query->select(\DB::raw(1))
                          ->from('events')
                          ->whereColumn('events.category_id', 'event_categories.id')
                          ->where('events.status', 'published')
                          ->where('events.is_active', true);
                })
                ->orderBy('name')
                ->get();
        }

        // Si c'est une requête API (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
        }

        return view('client.categories.index', compact('categories'));
    }

    /**
     * Display the specified category with its events.
     */
    public function show(Request $request, $slug)
    {
        $category = \DB::table('event_categories')
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ], 404);
            }
            abort(404, 'Catégorie non trouvée');
        }

        // Récupérer les événements de cette catégorie
        $events = \App\Models\Event::where('status', 'published')
            ->where('is_active', true)
            ->where('category_id', $category->id)
            ->with([
                'organizer:id,name,slug',
                'schedules' => function($q) {
                    $q->where('status', 'active')->orderBy('starts_at');
                },
                'ticketTypes' => function($q) {
                    $q->where('status', 'active')->orderBy('price');
                },
                'venue:id,name,city,address'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Si c'est une requête API (JSON)
        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'category' => $category,
                'events' => $events->items(),
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ]);
        }

        return view('client.categories.show', compact('category', 'events'));
    }
}