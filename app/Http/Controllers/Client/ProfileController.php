<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user profile.
     */
    public function show()
    {
        $user = Auth::user();
        
        // Statistiques utilisateur
        $stats = [
            'total_orders' => $user->orders()->count(),
            'total_tickets' => $user->tickets()->count(),
            'upcoming_events' => $user->tickets()
                ->whereHas('event.schedules', function ($query) {
                    $query->where('starts_at', '>', now());
                })
                ->count(),
            'total_spent' => $user->orders()
                ->where('status', 'paid')
                ->sum('total_amount')
        ];

        return view('client.profile.show', compact('user', 'stats'));
    }

    /**
     * Update the user profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'preferences' => ['nullable', 'array'],
        ]);

        // Vérifier le mot de passe actuel si un nouveau mot de passe est fourni
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
            }
        }

        // Mettre à jour les informations
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->has('preferences')) {
            $updateData['preferences'] = $request->preferences;
        }

        $user->update($updateData);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }
}