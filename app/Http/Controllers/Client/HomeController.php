<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index()
    {
        // Récupérer les événements à la une (les plus récents ou populaires)
        $featuredEvents = Event::where('status', 'published')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // Récupérer les événements à venir
        $upcomingEvents = Event::where('status', 'published')
            ->where('is_active', true)
            ->whereHas('schedules', function ($query) {
                $query->where('starts_at', '>', now());
            })
            ->limit(8)
            ->get();

        // Statistiques globales (optionnel)
        $stats = [
            'total_events' => Event::where('status', 'published')->count(),
            'total_organizers' => \App\Models\Organizer::where('is_active', true)->count(),
            'total_tickets_sold' => \App\Models\Ticket::where('status', 'issued')->count(),
        ];

        return view('client.home', compact('featuredEvents', 'upcomingEvents', 'stats'));
    }
}