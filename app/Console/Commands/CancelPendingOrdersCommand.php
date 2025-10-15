<?php

namespace App\Console\Commands;

use App\Jobs\CancelPendingOrders;
use Illuminate\Console\Command;

class CancelPendingOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Annuler automatiquement les commandes en attente depuis plus d\'1 heure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des commandes en attente expirées...');

        // Exécuter le job de manière synchrone
        $job = new CancelPendingOrders();
        $job->handle();

        $this->info('✅ Traitement terminé !');

        return Command::SUCCESS;
    }
}
