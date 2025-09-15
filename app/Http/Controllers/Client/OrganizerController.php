<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    /**
     * Display the specified organizer.
     */
    public function show(Organizer $organizer)
    {
        // Load related data
        $organizer->load([
            'events' => function ($query) {
                $query->published()
                      ->with(['schedules', 'venue', 'ticketTypes'])
                      ->orderBy('published_at', 'desc');
            }
        ]);

        return response()->json([
            'success' => true,
            'organizer' => $organizer,
            'events' => $organizer->events
        ]);
    }
}