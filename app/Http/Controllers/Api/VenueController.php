<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Liste des lieux
     */
    public function index(Request $request)
    {
        $venues = Venue::orderBy('city')
                      ->orderBy('name')
                      ->get(['id', 'name', 'city', 'address']);

        return response()->json([
            'success' => true,
            'data' => $venues,
            'venues' => $venues, // Pour la compatibilité
        ]);
    }
}