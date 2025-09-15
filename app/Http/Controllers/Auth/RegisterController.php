<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Where to redirect users after registration.
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application's registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'is_organizer' => 'nullable|boolean',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_organizer' => $request->boolean('is_organizer'),
            'status' => 'active',
        ]);

        // Assigner le rôle approprié
        $roleSlug = $request->boolean('is_organizer') ? Role::ORGANIZER : Role::CLIENT;
        $user->assignRole($roleSlug);

        // Connecter l'utilisateur
        Auth::login($user);

        $accessLevel = $user->getAccessLevel();
        
        $redirectUrl = match($accessLevel) {
            'admin' => route('admin.dashboard'),
            'organizer' => route('organizer.dashboard'),
            default => route('client.home'),
        };

        return redirect($redirectUrl)
            ->with('success', 'Compte créé avec succès ! Bienvenue ' . $user->name);
    }
}