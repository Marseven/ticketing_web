<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de fréquentation.
 *
 * L'application est une SPA : le serveur ne voit qu'un seul chargement de page
 * par visite, alors que la personne en parcourt dix. Le navigateur signale donc
 * lui-même chaque changement d'écran.
 *
 * ⚠️ Aucune adresse IP n'est conservée. Le visiteur est identifié par une
 * empreinte (IP + navigateur + sel du jour), qui change chaque jour et ne
 * permet pas de remonter à une personne : elle sert uniquement à ne pas
 * compter dix fois la même visite dans la journée. C'est ce que la politique
 * de confidentialité annonce, et ce que demande la loi n° 001/2011.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512);
            $table->char('visitor_hash', 64);
            $table->string('referrer_source', 64)->nullable();
            $table->string('device', 16)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Les trois lectures de la page de supervision : la courbe par
            // jour, les pages les plus vues, le décompte des visiteurs.
            $table->index('created_at');
            $table->index(['created_at', 'visitor_hash']);
            $table->index(['created_at', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
