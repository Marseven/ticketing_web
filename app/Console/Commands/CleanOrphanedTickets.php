<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanOrphanedTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:clean-orphaned {--dry-run : Afficher les tickets sans les modifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Annuler les tickets en statut pending dont la commande est annulée ou expirée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Recherche des tickets orphelins...');

        // Trouver les tickets en pending dont la commande n'est pas pending
        $orphanedTickets = Ticket::where('status', 'pending')
            ->whereHas('order', function($query) {
                $query->whereIn('status', ['cancelled', 'expired', 'failed']);
            })
            ->get();

        $this->info("📋 Tickets orphelins trouvés: {$orphanedTickets->count()}");

        if ($orphanedTickets->isEmpty()) {
            $this->info('✅ Aucun ticket orphelin à nettoyer.');
            return Command::SUCCESS;
        }

        // Afficher les détails
        $this->table(
            ['ID', 'Code', 'Commande', 'Statut Commande', 'Créé le'],
            $orphanedTickets->map(function($ticket) {
                return [
                    $ticket->id,
                    $ticket->code,
                    $ticket->order->reference ?? 'N/A',
                    $ticket->order->status ?? 'N/A',
                    $ticket->created_at->format('d/m/Y H:i'),
                ];
            })
        );

        if ($this->option('dry-run')) {
            $this->warn('⚠️ Mode dry-run: aucune modification effectuée.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Confirmer l\'annulation de ces tickets ?')) {
            $this->info('Opération annulée.');
            return Command::SUCCESS;
        }

        // Annuler les tickets
        $cancelled = 0;
        foreach ($orphanedTickets as $ticket) {
            $ticket->update([
                'status' => 'cancelled',
                'issued_at' => null
            ]);
            $cancelled++;
        }

        Log::info('🧹 Nettoyage tickets orphelins effectué', [
            'count' => $cancelled
        ]);

        $this->info("✅ {$cancelled} tickets annulés avec succès.");

        return Command::SUCCESS;
    }
}
