<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:clean-duplicates {order_id?} {--dry-run : Afficher les tickets à supprimer sans les supprimer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les tickets dupliqués pour une commande ou toutes les commandes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 MODE DRY-RUN : Aucune suppression ne sera effectuée');
        }

        if ($orderId) {
            $this->cleanOrderTickets($orderId, $dryRun);
        } else {
            $this->cleanAllDuplicates($dryRun);
        }

        return 0;
    }

    /**
     * Nettoyer les tickets d'une commande spécifique
     */
    private function cleanOrderTickets(int $orderId, bool $dryRun): void
    {
        $order = Order::find($orderId);

        if (!$order) {
            $this->error("❌ Commande #{$orderId} non trouvée");
            return;
        }

        $this->info("📦 Commande #{$order->id} - Référence: {$order->reference}");
        $this->info("   Status: {$order->status}");

        $tickets = $order->tickets()->orderBy('id', 'asc')->get();
        $ticketsCount = $tickets->count();

        $this->info("   Tickets trouvés: {$ticketsCount}");

        if ($ticketsCount <= 1) {
            $this->info("✅ Aucun doublon détecté");
            return;
        }

        // Garder le premier ticket (le plus ancien)
        $ticketToKeep = $tickets->first();
        $ticketsToDelete = $tickets->slice(1);

        $this->warn("⚠️  Tickets à supprimer: " . $ticketsToDelete->count());
        $this->info("✅ Ticket à garder: #{$ticketToKeep->id} - Code: {$ticketToKeep->code}");

        foreach ($ticketsToDelete as $ticket) {
            $this->line("   ❌ Ticket #{$ticket->id} - Code: {$ticket->code} - Créé: {$ticket->created_at}");
        }

        if (!$dryRun) {
            if ($this->confirm('Confirmer la suppression des ' . $ticketsToDelete->count() . ' tickets dupliqués ?')) {
                DB::beginTransaction();
                try {
                    foreach ($ticketsToDelete as $ticket) {
                        $ticket->delete();
                        $this->info("   🗑️  Ticket #{$ticket->id} supprimé");
                    }
                    DB::commit();
                    $this->info("✅ Nettoyage terminé avec succès");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("❌ Erreur: " . $e->getMessage());
                }
            } else {
                $this->info("❌ Opération annulée");
            }
        }
    }

    /**
     * Nettoyer tous les doublons
     */
    private function cleanAllDuplicates(bool $dryRun): void
    {
        $this->info("🔍 Recherche de toutes les commandes avec tickets dupliqués...");

        // Trouver les commandes avec plus d'1 ticket
        $ordersWithDuplicates = Order::withCount('tickets')
            ->having('tickets_count', '>', 1)
            ->get();

        if ($ordersWithDuplicates->isEmpty()) {
            $this->info("✅ Aucune commande avec tickets dupliqués trouvée");
            return;
        }

        $this->warn("⚠️  {$ordersWithDuplicates->count()} commandes avec tickets multiples trouvées");
        $this->newLine();

        foreach ($ordersWithDuplicates as $order) {
            $this->cleanOrderTickets($order->id, $dryRun);
            $this->newLine();
        }
    }
}
