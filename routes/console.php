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
// Toutes les 15 min : les places d'une commande abandonnée sont désormais
// comptées comme occupées, donc elles doivent être relâchées vite.
// Battement du planificateur. Sans cette trace, rien ne distingue « aucune
// tâche à faire » de « le cron ne tourne plus » — or dans le second cas les
// paiements en attente ne sont plus vérifiés et les versements s'arrêtent.
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::put(
        \App\Http\Controllers\Admin\SupervisionController::HEARTBEAT_KEY,
        now()->toIso8601String(),
        now()->addDay()
    );
})->everyMinute()->name('supervision-heartbeat')->withoutOverlapping();

Schedule::job(new CancelPendingOrders)->everyFifteenMinutes();

// La notification d'e-billing se perd parfois : le client est débité mais son
// billet n'est jamais émis. On va donc demander l'état des paiements en
// attente plutôt que d'attendre une notification qui ne viendra pas.
Schedule::command('payments:check-pending')->everyFiveMinutes()->withoutOverlapping();

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

// Traiter les jobs de la queue "database" (ex : import legacy en arrière-plan)
// sans worker permanent : à chaque minute, on vide la queue puis on s'arrête.
// withoutOverlapping(30) : verrou de 30 min (> --max-time=25 min) au lieu du
// défaut 24 h — si un worker est tué (hébergement mutualisé), le verrou expire
// vite et l'import suivant peut démarrer, au lieu de rester bloqué une journée.
Schedule::command('queue:work database --stop-when-empty --max-time=1500 --tries=1')
    ->everyMinute()
    ->withoutOverlapping(30);
