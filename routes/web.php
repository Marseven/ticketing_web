<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Ici on définit les routes web pour l'application. Ces routes sont chargées
| par le RouteServiceProvider et assignées au groupe middleware "web".
|
*/

// Routes d'authentification (login, register, logout)
Route::middleware('guest')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    
    // Route temporaire pour mot de passe oublié
    Route::get('password/reset', function () {
        return redirect()->route('login')->with('info', 'Fonctionnalité de récupération de mot de passe bientôt disponible.');
    })->name('password.request');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});

// Redirection automatique après connexion selon le rôle
Route::get('dashboard', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    // Rediriger vers l'application Vue.js
    return redirect()->route('spa');
})->name('dashboard')->middleware('auth');

// Route principale pour l'application Vue.js
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api|admin|organizer).*$')->name('spa');

// Routes pour l'espace ORGANISATEUR
Route::prefix('organizer')->name('organizer.')->middleware(['auth', 'organizer.access'])->group(function () {
    Route::get('/', function () {
        return view('organizer.dashboard');
    })->name('dashboard');
    
    // Gestion des événements
    Route::resource('events', App\Http\Controllers\Organizer\EventController::class);
    Route::get('events/{event}/statistics', [App\Http\Controllers\Organizer\EventController::class, 'statistics'])->name('events.statistics');
    Route::get('events/{event}/attendees', [App\Http\Controllers\Organizer\EventController::class, 'attendees'])->name('events.attendees');
    
    // Gestion des billets et scanning
    Route::get('events/{event}/tickets', [App\Http\Controllers\Organizer\TicketController::class, 'index'])->name('events.tickets');
    Route::get('scanning', [App\Http\Controllers\Organizer\ScanController::class, 'index'])->name('scanning.index');
    Route::get('scanning/{event}', [App\Http\Controllers\Organizer\ScanController::class, 'event'])->name('scanning.event');
    
    // Gestion des commandes
    Route::get('orders', [App\Http\Controllers\Organizer\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [App\Http\Controllers\Organizer\OrderController::class, 'show'])->name('orders.show');
    
    // Profil organisateur
    Route::get('profile', [App\Http\Controllers\Organizer\ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [App\Http\Controllers\Organizer\ProfileController::class, 'update'])->name('profile.update');
    
    // Paramètres
    Route::get('settings', [App\Http\Controllers\Organizer\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [App\Http\Controllers\Organizer\SettingsController::class, 'update'])->name('settings.update');
});

// Routes pour l'espace ADMIN
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin.access'])->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // Gestion des utilisateurs
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::put('users/{user}/roles', [App\Http\Controllers\Admin\UserController::class, 'updateRoles'])->name('users.roles');
    Route::put('users/{user}/status', [App\Http\Controllers\Admin\UserController::class, 'updateStatus'])->name('users.status');
    
    // Gestion des organisateurs
    Route::resource('organizers', App\Http\Controllers\Admin\OrganizerController::class);
    Route::put('organizers/{organizer}/status', [App\Http\Controllers\Admin\OrganizerController::class, 'updateStatus'])->name('organizers.status');
    
    // Gestion des événements
    Route::resource('events', App\Http\Controllers\Admin\EventController::class);
    Route::put('events/{event}/status', [App\Http\Controllers\Admin\EventController::class, 'updateStatus'])->name('events.status');
    
    // Gestion des rôles et permissions
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
    Route::resource('privileges', App\Http\Controllers\Admin\PrivilegeController::class);
    
    // Rapports et statistiques
    Route::get('reports', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/events', [App\Http\Controllers\Admin\ReportController::class, 'events'])->name('reports.events');
    Route::get('reports/sales', [App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/users', [App\Http\Controllers\Admin\ReportController::class, 'users'])->name('reports.users');
    
    // Paramètres système
    Route::get('settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    
    // Logs système
    Route::get('logs', [App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');
});
