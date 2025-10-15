<?php

namespace App\Jobs;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CancelPendingOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔍 Démarrage du job CancelPendingOrders');

        // Récupérer les commandes en attente depuis plus d'1 heure
        $oneHourAgo = Carbon::now()->subHour();

        $pendingOrders = Order::where('status', 'pending')
            ->where('created_at', '<', $oneHourAgo)
            ->get();

        Log::info('📋 Commandes en attente trouvées', [
            'count' => $pendingOrders->count(),
            'cutoff_time' => $oneHourAgo->toDateTimeString()
        ]);

        $cancelledCount = 0;

        foreach ($pendingOrders as $order) {
            try {
                $age = Carbon::now()->diffInMinutes($order->created_at);

                Log::info('⏰ Traitement commande expirée', [
                    'reference' => $order->reference,
                    'created_at' => $order->created_at,
                    'age_minutes' => $age
                ]);

                // Annuler la commande
                $order->status = 'cancelled';
                $order->save();

                // Libérer les billets réservés (si applicable)
                // Si vous avez un système de réservation de billets, libérez-les ici
                // Exemple:
                // $order->tickets()->delete();

                $cancelledCount++;

                Log::info('✅ Commande annulée', [
                    'reference' => $order->reference,
                    'age_minutes' => $age
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Erreur lors de l\'annulation de la commande', [
                    'reference' => $order->reference,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('✅ Job CancelPendingOrders terminé', [
            'total_found' => $pendingOrders->count(),
            'cancelled' => $cancelledCount
        ]);
    }
}
