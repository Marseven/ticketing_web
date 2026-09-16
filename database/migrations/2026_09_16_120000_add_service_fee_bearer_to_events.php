<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qui supporte les frais de service (e-billing, 2,5 %) PAR ÉVÉNEMENT,
     * décidé à la création :
     * - 'customer' : les frais sont AJOUTÉS au prix payé par le client
     *                (ligne « Frais de service » visible au paiement).
     * - 'platform' : la plateforme absorbe les frais ; le client paie
     *                exactement le prix affiché (frais invisibles).
     *
     * Défaut 'platform' → les événements existants gardent leur comportement
     * actuel (aucun frais ajouté au client).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->enum('service_fee_bearer', ['customer', 'platform'])
                ->default('platform')
                ->after('commission_percentage');

            $table->index(['service_fee_bearer']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['service_fee_bearer']);
            $table->dropColumn('service_fee_bearer');
        });
    }
};
