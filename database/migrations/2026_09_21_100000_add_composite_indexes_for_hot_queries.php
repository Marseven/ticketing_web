<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 0 perf — index composites pour les requêtes chaudes sous charge :
     *  - comptage des ventes par type (liste/fiche publiques, checkout) :
     *      tickets WHERE ticket_type_id IN (...) AND status IN ('issued','used')
     *  - stats par événement (suivi public, admin, lots physiques) :
     *      tickets WHERE event_id = ? AND status = ?
     *  - anti-double-scan (validation) :
     *      checkins WHERE ticket_id = ? AND result = 'valid'
     * Les index mono-colonne existent déjà ; les composites évitent le
     * filtrage secondaire sur de grosses tables.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['ticket_type_id', 'status'], 'tickets_type_status_idx');
            $table->index(['event_id', 'status'], 'tickets_event_status_idx');
        });

        Schema::table('checkins', function (Blueprint $table) {
            $table->index(['ticket_id', 'result'], 'checkins_ticket_result_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_type_status_idx');
            $table->dropIndex('tickets_event_status_idx');
        });

        Schema::table('checkins', function (Blueprint $table) {
            $table->dropIndex('checkins_ticket_result_idx');
        });
    }
};
