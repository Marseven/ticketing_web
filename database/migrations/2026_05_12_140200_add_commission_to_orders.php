<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fige le taux de commission réellement appliqué à la commande, pour
     * l'historique/comptabilité (le taux de l'événement peut changer plus tard).
     * Modèle DÉDUIT : subtotal_amount devient le NET organisateur
     * (base - commission), fees_amount = la commission retenue.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('fees_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('commission_percentage');
        });
    }
};
