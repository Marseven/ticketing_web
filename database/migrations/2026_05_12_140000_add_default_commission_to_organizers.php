<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Commission par défaut d'un organisateur (modèle DÉDUIT : la plateforme
     * retient ce pourcentage sur le prix de base ; l'organisateur reçoit le
     * reste). Peut être surchargé par événement (cf. events.commission_percentage).
     */
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->decimal('default_commission_percentage', 5, 2)->default(10.00)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn('default_commission_percentage');
        });
    }
};
