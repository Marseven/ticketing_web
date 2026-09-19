<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Co-organisateur (2e organisateur) d'un événement — pour l'AFFICHAGE /
     * crédit uniquement (« Organisée par X et Y »). Toute la logique financière
     * (payout, solde, commission) reste liée à l'organisateur PRINCIPAL
     * (organizer_id). Nullable : la plupart des events n'en ont pas.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('co_organizer_id')->nullable()->after('organizer_id');
            $table->foreign('co_organizer_id')->references('id')->on('organizers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['co_organizer_id']);
            $table->dropColumn('co_organizer_id');
        });
    }
};
