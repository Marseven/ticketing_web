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

// Routes publiques pour les paiements
Route::prefix('payment')->group(function () {
    Route::get('success/{reference}', function ($reference) {
        return redirect('/payment-success?reference=' . $reference);
    })->name('payment.success');
    
    Route::get('cancel/{reference}', function ($reference) {
        return redirect('/payment-cancel?reference=' . $reference);
    })->name('payment.cancel');
});

// Routes d'authentification (sans préfixe v1 pour correspondre aux annotations)
Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');

// Routes de réinitialisation de mot de passe
Route::post('forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'forgotPassword']);
Route::post('reset-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'resetPassword']);
Route::post('verify-reset-token', [App\Http\Controllers\Auth\PasswordResetController::class, 'verifyToken']);

// Route publique pour servir les images (fallback) - SUPPRIMÉE car elle va dans routes/web.php

// Routes des événements (sans préfixe v1 pour correspondre aux annotations)
Route::prefix('events')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EventController::class, 'index']);
    Route::get('{event:slug}', [App\Http\Controllers\Api\EventController::class, 'show']);
    Route::get('{event:slug}/scan-stats', [App\Http\Controllers\Api\EventController::class, 'scanStats']);
});

Route::prefix('v1')->group(function () {
    
    // Catégories (accès public)
    Route::get('categories', [App\Http\Controllers\Client\CategoryController::class, 'index']);
    
    // Lieux (accès public pour la lecture)
    Route::get('venues', [App\Http\Controllers\Api\VenueController::class, 'index']);
    
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
    Route::prefix('payments')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PaymentController::class, 'payments']);
            Route::get('{id}', [App\Http\Controllers\Api\PaymentController::class, 'payment']);
            Route::get('{id}/status', [App\Http\Controllers\Api\PaymentController::class, 'getPaymentStatus']);
        });
        
        // Routes publiques pour les paiements invités
        Route::post('initiate', [App\Http\Controllers\Api\PaymentController::class, 'initiateGuestPayment']);
        Route::post('push-ussd', [App\Http\Controllers\Api\PaymentController::class, 'pushUSSD']);
        Route::post('kyc', [App\Http\Controllers\Api\PaymentController::class, 'checkKYC']);
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
        Route::get('dashboard/stats', [App\Http\Controllers\Api\OrganizerController::class, 'dashboardStats']);
        Route::get('events/recent', [App\Http\Controllers\Api\OrganizerController::class, 'recentEvents']);
        Route::get('notifications', [App\Http\Controllers\Api\OrganizerController::class, 'notifications']);
        Route::get('profile', [App\Http\Controllers\Api\OrganizerController::class, 'profile']);
        Route::put('profile', [App\Http\Controllers\Api\OrganizerController::class, 'updateProfile']);
        Route::post('profile/avatar', [App\Http\Controllers\Api\OrganizerController::class, 'uploadAvatar']);
        Route::get('balances', [App\Http\Controllers\Api\OrganizerController::class, 'balances']);
        Route::post('payouts', [App\Http\Controllers\Api\OrganizerController::class, 'requestPayout']);
        Route::get('payouts', [App\Http\Controllers\Api\OrganizerController::class, 'payouts']);
        Route::get('events', [App\Http\Controllers\Api\OrganizerController::class, 'events']);
        Route::post('events', [App\Http\Controllers\Api\OrganizerController::class, 'createEvent']);
        Route::put('events/{id}', [App\Http\Controllers\Api\OrganizerController::class, 'updateEvent']);
        Route::get('events/{id}', [App\Http\Controllers\Api\OrganizerController::class, 'getEvent']);
        Route::get('events/{id}/stats', [App\Http\Controllers\Api\OrganizerController::class, 'getEventStats']);
        Route::get('events/{eventId}/sales', [App\Http\Controllers\Api\OrganizerController::class, 'eventSales']);
        Route::get('payments', [App\Http\Controllers\Api\OrganizerController::class, 'payments']);
        Route::get('balance', [App\Http\Controllers\Api\OrganizerController::class, 'getBalance']);
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

    // Routes de gestion des images
    Route::prefix('images')->middleware('auth:sanctum')->group(function () {
        Route::post('upload', [App\Http\Controllers\Api\ImageController::class, 'upload']);
        Route::post('validate-url', [App\Http\Controllers\Api\ImageController::class, 'validateUrl']);
        Route::delete('delete', [App\Http\Controllers\Api\ImageController::class, 'delete']);
        Route::get('debug/{type}', [App\Http\Controllers\Api\ImageController::class, 'debug']);
    });
    
    // Webhooks pour les paiements
    Route::prefix('webhooks')->group(function () {
        Route::post('airtel', [App\Http\Controllers\Api\WebhookController::class, 'airtel'])->name('webhook.airtel');
        Route::post('moov', [App\Http\Controllers\Api\WebhookController::class, 'moov'])->name('webhook.moov');
        Route::post('card', [App\Http\Controllers\Api\WebhookController::class, 'card'])->name('webhook.card');
        Route::post('ebilling', [App\Http\Controllers\Api\WebhookController::class, 'ebilling'])->name('webhook.ebilling');
        Route::post('shap-payout', [App\Http\Controllers\Api\WebhookController::class, 'shapPayout'])->name('webhook.shap-payout');
    });

    // Routes d'administration
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin.access'])->group(function () {
        // Dashboard
        Route::get('dashboard', [App\Http\Controllers\Admin\AdminController::class, 'dashboard']);
        
        // Gestion des utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'users']);
            Route::post('/', [App\Http\Controllers\Admin\AdminController::class, 'createUser']);
            Route::put('{user}', [App\Http\Controllers\Admin\AdminController::class, 'updateUser']);
            Route::post('{user}/toggle-status', [App\Http\Controllers\Admin\AdminController::class, 'toggleUserStatus']);
            Route::post('{user}/reset-password', [App\Http\Controllers\Admin\AdminController::class, 'resetUserPassword']);
        });
        
        // Gestion des organisateurs
        Route::prefix('organizers')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'organizers']);
            Route::post('/', [App\Http\Controllers\Admin\AdminController::class, 'createOrganizer']);
            Route::put('{organizer}', [App\Http\Controllers\Admin\AdminController::class, 'updateOrganizer']);
            Route::put('{organizer}/auto-payout-config', [App\Http\Controllers\Admin\PayoutController::class, 'updateAutoPayoutConfig']);
        });
        
        // Gestion des événements
        Route::prefix('events')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'events']);
            Route::post('/', [App\Http\Controllers\Admin\AdminController::class, 'createEvent']);
            Route::get('{event}', [App\Http\Controllers\Admin\AdminController::class, 'showEvent']);
            Route::put('{event}', [App\Http\Controllers\Admin\AdminController::class, 'updateEvent']);
            Route::post('{event}/toggle-status', [App\Http\Controllers\Admin\AdminController::class, 'toggleEventStatus']);
        });
        
        // Gestion des payouts
        Route::prefix('payouts')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PayoutController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Admin\PayoutController::class, 'store']);
            Route::get('stats', [App\Http\Controllers\Admin\PayoutController::class, 'stats']);
            Route::get('balances', [App\Http\Controllers\Admin\PayoutController::class, 'balances']);
            Route::get('shap-balance', [App\Http\Controllers\Admin\PayoutController::class, 'shapBalance']);
            Route::post('check-all-pending', [App\Http\Controllers\Admin\PayoutController::class, 'checkAllPending']);
            Route::get('{payout}', [App\Http\Controllers\Admin\PayoutController::class, 'show']);
            Route::post('{payout}/check-status', [App\Http\Controllers\Admin\PayoutController::class, 'checkStatus']);
        });
        
        // Gestion des catégories
        Route::prefix('categories')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'categories']);
            Route::post('/', [App\Http\Controllers\Admin\AdminController::class, 'createCategory']);
            Route::put('{category}', [App\Http\Controllers\Admin\AdminController::class, 'updateCategory']);
            Route::delete('{category}', [App\Http\Controllers\Admin\AdminController::class, 'deleteCategory']);
        });
        
        // Gestion des lieux
        Route::prefix('venues')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'venues']);
            Route::post('/', [App\Http\Controllers\Admin\AdminController::class, 'createVenue']);
            Route::put('{venue}', [App\Http\Controllers\Admin\AdminController::class, 'updateVenue']);
            Route::delete('{venue}', [App\Http\Controllers\Admin\AdminController::class, 'deleteVenue']);
        });
        
        // Gestion des rapports
        Route::prefix('reports')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index']);
            Route::post('/generate', [App\Http\Controllers\Admin\ReportController::class, 'generate']);
            Route::get('{report}/download', [App\Http\Controllers\Admin\ReportController::class, 'download']);
            Route::delete('{report}', [App\Http\Controllers\Admin\ReportController::class, 'destroy']);
            Route::delete('/', [App\Http\Controllers\Admin\ReportController::class, 'clear']);
        });
    });
});

// Routes générales pour les ressources partagées
Route::get('categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
Route::get('venues', [App\Http\Controllers\Api\VenueController::class, 'index']);