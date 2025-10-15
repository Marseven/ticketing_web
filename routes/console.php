<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CancelPendingOrders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier l'annulation automatique des commandes en attente depuis plus d'1 heure
// Exécuté toutes les 30 minutes
Schedule::job(new CancelPendingOrders)->everyThirtyMinutes();
