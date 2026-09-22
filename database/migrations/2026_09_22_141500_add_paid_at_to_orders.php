<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Date à laquelle une commande a été réglée.
 *
 * `placed_at` dit quand le panier a été validé ; rien ne disait jusqu'ici quand
 * l'argent est effectivement arrivé. La différence compte pour la
 * réconciliation : une commande passée à 18 h et payée à 18 h 40 n'est pas la
 * même histoire qu'un paiement immédiat.
 *
 * Plusieurs chemins voulaient déjà l'écrire (webhook, vérification périodique,
 * validation manuelle par un administrateur, commande gratuite) — faute de
 * colonne, l'écriture partait dans le vide.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'paid_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('placed_at');
        });

        // Commandes déjà payées avant l'ajout de la colonne : on se rabat sur la
        // date du paiement réussi, et à défaut sur la date de mise à jour.
        DB::table('orders')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update([
                'paid_at' => DB::raw(
                    '(select max(paid_at) from payments'
                    . " where payments.order_id = orders.id and payments.status = 'success')"
                ),
            ]);

        DB::table('orders')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'paid_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
