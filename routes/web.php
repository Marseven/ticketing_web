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

// Route pour servir les banners (fallback)
Route::get('storage/banners/{filename}', [App\Http\Controllers\Api\ImageController::class, 'serve'])
    ->where('filename', '.*')
    ->middleware('throttle:1000,1');

// Plan du site : donne les pages d'événements aux moteurs de recherche, qui
// ne suivent pas toujours les liens rendus en JavaScript.
Route::get('sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Route catch-all pour Vue Router - DOIT être en dernier
//
// Les métadonnées sont résolues ICI et non dans le navigateur : WhatsApp,
// Facebook et les moteurs de recherche lisent le HTML servi sans exécuter le
// JavaScript. Un lien d'événement partagé doit donc porter son affiche et son
// titre dès la réponse du serveur.
Route::get('/{any}', function (\Illuminate\Http\Request $request, \App\Services\PageMeta $meta) {
    return view('app', ['meta' => $meta->forPath($request->path())]);
})->where('any', '.*')->name('spa');
