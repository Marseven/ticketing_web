<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Recalcule correctement les subtotal_amounts des commandes existantes.
     *
     * MODÈLE CORRECT:
     * - subtotal_amount = montant BRUT que l'organisateur reçoit (100% du prix de base)
     * - fees_amount = 5% du prix de base (payé en PLUS par le client)
     * - tax_amount = 18% des frais (payé en PLUS par le client)
     * - total_amount = subtotal_amount + fees_amount + tax_amount
     *
     * Formule inverse: subtotal_amount = total_amount / 1.059
     * Car: total = base × (1 + 0.05 + 0.05×0.18) = base × 1.059
     */
    public function up(): void
    {
        Log::info('🔧 Migration: Début du recalcul des subtotal_amounts des commandes');

        $orders = DB::table('orders')->get();
        $fixed = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            // Calculer le subtotal_amount correct à partir du total_amount
            // total_amount = subtotal_amount × 1.059
            // donc subtotal_amount = total_amount / 1.059
            $correctSubtotal = round($order->total_amount / 1.059, 2);

            // Calculer aussi les fees et taxes corrects
            $correctFees = round($correctSubtotal * 0.05, 2);
            $correctTax = round($correctFees * 0.18, 2);

            // Vérifier si les valeurs actuelles sont incorrectes
            $needsFix = abs($order->subtotal_amount - $correctSubtotal) > 0.5;

            if ($needsFix) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'subtotal_amount' => $correctSubtotal,
                        'fees_amount' => $correctFees,
                        'tax_amount' => $correctTax,
                    ]);

                Log::info('✅ Commande corrigée', [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'old_subtotal' => $order->subtotal_amount,
                    'new_subtotal' => $correctSubtotal,
                    'total_amount' => $order->total_amount,
                ]);

                $fixed++;
            } else {
                $skipped++;
            }
        }

        Log::info('🎉 Migration terminée', [
            'total_orders' => $orders->count(),
            'fixed' => $fixed,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback car on ne peut pas retrouver les anciennes valeurs incorrectes
        Log::warning('⚠️ Migration rollback: Impossible de restaurer les anciennes valeurs incorrectes');
    }
};
