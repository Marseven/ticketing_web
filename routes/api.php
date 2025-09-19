<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Routes pour la billetterie

// Routes publiques pour les clients
Route::prefix('client')->group(function () {
    // Événements publics (sans authentification)
    Route::get('events', [App\Http\Controllers\Client\EventController::class, 'index']);
    Route::get('events/{event:slug}', [App\Http\Controllers\Client\EventController::class, 'show']);
    
    // Catégories publiques
    Route::get('categories', [App\Http\Controllers\Client\CategoryController::class, 'index']);
    Route::get('categories/{slug}', [App\Http\Controllers\Client\CategoryController::class, 'show']);
    
    // Organisateurs publics
    Route::get('organizers/{organizer:slug}', [App\Http\Controllers\Client\OrganizerController::class, 'show']);
    
    // Routes avec authentification optionnelle
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('orders', [App\Http\Controllers\Client\OrderController::class, 'index']);
        Route::post('orders', [App\Http\Controllers\Client\OrderController::class, 'store']);
        Route::get('tickets', [App\Http\Controllers\Client\TicketController::class, 'index']);
    });
});

// Routes publiques pour les achats invités
Route::prefix('guest')->group(function () {
    // Commandes invité (sans authentification)
    Route::post('orders', [App\Http\Controllers\Guest\OrderController::class, 'store']);
    Route::get('orders/{reference}', [App\Http\Controllers\Guest\OrderController::class, 'show']);
    
    // Billets invité
    Route::get('tickets/{code}', [App\Http\Controllers\Guest\TicketController::class, 'show']);
    Route::get('tickets/retrieve/{email}', [App\Http\Controllers\Guest\TicketController::class, 'retrieve']);
});

// Routes d'authentification (sans préfixe v1 pour correspondre aux annotations)
Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');

// Routes des événements (sans préfixe v1 pour correspondre aux annotations)
Route::prefix('events')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EventController::class, 'index']);
    Route::get('{event:slug}', [App\Http\Controllers\Api\EventController::class, 'show']);
    Route::get('{event:slug}/scan-stats', [App\Http\Controllers\Api\EventController::class, 'scanStats']);
});

Route::prefix('v1')->group(function () {
    
    // Routes d'authentification
    Route::prefix('auth')->group(function () {
        Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
        Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
        Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
        Route::post('refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh'])->middleware('auth:sanctum');
    });

    
    // Routes de commande et paiement
    Route::prefix('orders')->middleware('auth:sanctum')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::get('/', [App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::get('{order}', [App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::post('{order}/pay', [App\Http\Controllers\Api\PaymentController::class, 'processPayment']);
    });

    // Routes de paiement
    Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PaymentController::class, 'payments']);
        Route::get('{id}', [App\Http\Controllers\Api\PaymentController::class, 'payment']);
        Route::get('{id}/status', [App\Http\Controllers\Api\PaymentController::class, 'getPaymentStatus']);
    });

    // Routes des tickets
    Route::prefix('tickets')->group(function () {
        Route::get('retrieve/{token}', [App\Http\Controllers\Api\TicketController::class, 'retrieve']);
        Route::post('validate', [App\Http\Controllers\Api\TicketController::class, 'validateTicket'])->middleware('auth:sanctum');
        Route::get('{code}', [App\Http\Controllers\Api\TicketController::class, 'show'])->name('api.tickets.validate');
    });

    // Routes QR Codes sécurisés
    Route::prefix('qrcodes')->middleware('auth:sanctum')->group(function () {
        Route::get('tickets/{ticketId}/secure', [App\Http\Controllers\Api\QRCodeController::class, 'generateSecureQR']);
        Route::post('analyze', [App\Http\Controllers\Api\QRCodeController::class, 'analyzeQRCode']);
        Route::get('tickets/{ticketId}/compare', [App\Http\Controllers\Api\QRCodeController::class, 'compareQRFormats']);
    });

    // Routes de l'espace organisateur
    Route::prefix('organizer')->middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\Api\OrganizerController::class, 'dashboard']);
        Route::get('/', [App\Http\Controllers\Api\OrganizerController::class, 'index']);
        Route::get('{organizer:slug}', [App\Http\Controllers\Api\OrganizerController::class, 'show']);
        Route::get('{organizer:slug}/stats', [App\Http\Controllers\Api\OrganizerController::class, 'stats']);
    });

    // Routes des scans
    Route::prefix('scans')->middleware('auth:sanctum')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\ScanController::class, 'store']);
        Route::post('bulk', [App\Http\Controllers\Api\ScanController::class, 'bulk']);
        Route::get('/', [App\Http\Controllers\Api\ScanController::class, 'index']);
    });

    // Webhooks pour les paiements
    Route::prefix('webhooks')->group(function () {
        Route::post('airtel', [App\Http\Controllers\Api\WebhookController::class, 'airtel'])->name('webhook.airtel');
        Route::post('moov', [App\Http\Controllers\Api\WebhookController::class, 'moov'])->name('webhook.moov');
        Route::post('card', [App\Http\Controllers\Api\WebhookController::class, 'card'])->name('webhook.card');
    });
});