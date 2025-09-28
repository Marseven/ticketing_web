<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SPA (Single Page Application)
|--------------------------------------------------------------------------
|
| Toutes les routes sont gérées par Vue.js côté client.
| Les APIs sont dans routes/api.php
|
*/

// Route pour servir les images (fallback)
Route::get('storage/images/{type}/{filename}', [App\Http\Controllers\Api\ImageController::class, 'serve'])
    ->where('type', 'events|venues|users|organizers')
    ->where('filename', '.*')
    ->middleware('throttle:1000,1');

// Route catch-all pour Vue Router - DOIT être en dernier
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
