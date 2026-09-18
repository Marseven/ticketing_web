<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jeton public (non devinable) permettant à un organisateur non technique
     * d'accéder à la page de suivi des billets d'un événement sans se connecter.
     * Régénérable pour révoquer un lien partagé.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn('tracking_token');
        });
    }
};
