<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Where to redirect users after login.
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // Peut être email ou téléphone
            'password' => 'required|string',
        ]);

        $login = $request->login;
        $password = $request->password;
        $remember = $request->boolean('remember');

        // Déterminer si c'est un email ou un téléphone
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        // Normaliser le numéro de téléphone si nécessaire
        if ($fieldType === 'phone') {
            $login = $this->normalizePhoneNumber($login);
        }

        $credentials = [
            $fieldType => $login,
            'password' => $password
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Mettre à jour le last_login_at
            $user->update(['last_login_at' => now()]);
            
            // Assigner un rôle par défaut si l'utilisateur n'en a pas
            if (!$user->roles()->exists()) {
                $defaultRole = $user->is_organizer ? Role::ORGANIZER : Role::CLIENT;
                $user->assignRole($defaultRole);
            }

            // Redirection basée sur le niveau d'accès
            $accessLevel = $user->getAccessLevel();
            
            if ($request->expectsJson()) {
                $token = $user->createToken('auth_token')->plainTextToken;
                
                return response()->json([
                    'success' => true,
                    'user' => $user->load('roles'),
                    'token' => $token,
                    'access_level' => $accessLevel,
                    'message' => 'Connexion réussie ! Bienvenue ' . $user->name
                ]);
            }
            
            $redirectUrl = match($accessLevel) {
                'admin' => route('admin.dashboard'),
                'organizer' => route('organizer.dashboard'),
                default => route('client.home'),
            };

            return redirect()->intended($redirectUrl)
                ->with('success', 'Connexion réussie ! Bienvenue ' . $user->name);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Les informations de connexion sont incorrectes.'
            ], 401);
        }

        throw ValidationException::withMessages([
            'login' => ['Les informations de connexion sont incorrectes.'],
        ]);
    }

    /**
     * Normaliser le numéro de téléphone
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques sauf le + au début
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Si le numéro commence par 0, le remplacer par +241 (Gabon)
        if (str_starts_with($phone, '0')) {
            $phone = '+241' . substr($phone, 1);
        }
        
        // Si le numéro ne commence pas par +, ajouter +241
        if (!str_starts_with($phone, '+')) {
            $phone = '+241' . $phone;
        }
        
        return $phone;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'Déconnexion réussie !');
    }
}