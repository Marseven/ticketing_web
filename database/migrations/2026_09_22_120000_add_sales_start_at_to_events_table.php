<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date d'ouverture de la billetterie.
 *
 * L'événement est annoncé (affiche, réseaux) avant que la vente ouvre : il doit
 * être visible sur la plateforme, avec un compte à rebours, sans que personne
 * puisse acheter avant l'heure dite.
 *
 * Nulle par défaut : la vente est ouverte dès la publication, comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('sales_start_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('sales_start_at');
        });
    }
};
