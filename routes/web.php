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

// Route catch-all pour Vue Router - DOIT être en dernier
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*')->name('spa');
