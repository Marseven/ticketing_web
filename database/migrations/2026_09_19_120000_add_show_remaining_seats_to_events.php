<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Affichage des places restantes au public : optionnel par événement,
     * MASQUÉ par défaut (l'info reste dispo côté organisateur/admin). Certains
     * promoteurs ne veulent pas exposer leur rythme de vente.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('show_remaining_seats')->default(false)->after('use_variable_pricing');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('show_remaining_seats');
        });
    }
};
