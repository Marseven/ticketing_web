<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Choix du mode de versement PAR ÉVÉNEMENT (décidé à la création) :
     * - 'instant'  : à chaque vente, le net est immédiatement reversé au
     *                numéro saisi par l'organisateur (instant_payout_phone).
     * - 'deferred' : le net s'accumule sur le solde et est reversé en fin
     *                d'événement (règlement via Bamboo/SHAP).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('payout_mode', ['deferred', 'instant'])
                ->default('deferred')
                ->after('commission_percentage');
            $table->string('instant_payout_phone')->nullable()->after('payout_mode');
            $table->datetime('payout_settled_at')->nullable()->after('instant_payout_phone');

            $table->index(['payout_mode']);
        });

        // SÉCURITÉ ANTI-DOUBLE-PAIEMENT : tous les événements existants sont
        // marqués « déjà réglés ». Leurs versements ont été gérés par l'ancien
        // flux ; sans ce backfill, la commande payout:settle-ended-events
        // re-verserait de l'argent déjà disbursé. Seuls les NOUVEAUX
        // événements (payout_settled_at = null) seront réglés par le nouveau flux.
        DB::table('events')->update(['payout_settled_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['payout_mode']);
            $table->dropColumn(['payout_mode', 'instant_payout_phone', 'payout_settled_at']);
        });
    }
};
