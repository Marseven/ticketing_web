<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CancelPendingOrders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Planifier l'annulation automatique des commandes en attente depuis plus d'1 heure
// Exécuté toutes les heures
Schedule::job(new CancelPendingOrders)->hourly();

// Vérifier les payouts asynchrones SHAP (pending/processing) toutes les 5 min.
// Compatible mutualisé (Hostinger): un unique cron `schedule:run` suffit,
// pas besoin de queue worker permanent.
Schedule::command('payout:check-status')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Régler les événements en mode différé dont toutes les dates sont passées
// (versement en fin d'événement). Toutes les heures.
Schedule::command('payout:settle-ended-events')
    ->hourly()
    ->withoutOverlapping();
