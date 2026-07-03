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
     * Tickets physiques : l'admin génère des QR imprimés sur des billets
     * physiques (sans achat en ligne). On distingue la provenance et on
     * groupe les billets par lot (batch).
     *
     * - ticket_source : online (achat), physical (imprimé), comped (offert)
     * - batch_reference : identifiant du lot de génération (pour l'impression
     *   et le suivi)
     * - order_id devient nullable : un billet physique n'a pas de commande.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('ticket_source', ['online', 'physical', 'comped'])
                ->default('online')
                ->after('status');
            $table->string('batch_reference')->nullable()->after('ticket_source');
            $table->index(['ticket_source']);
            $table->index(['batch_reference']);
        });

        // order_id et ticket_type_id nullables : un billet physique/comped n'a
        // pas de commande, et peut être générique (sans type précis).
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            Schema::table('tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
                $table->unsignedBigInteger('ticket_type_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE tickets MODIFY COLUMN order_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE tickets MODIFY COLUMN ticket_type_id BIGINT UNSIGNED NULL');
        }

        // Les billets existants proviennent tous d'achats en ligne.
        DB::table('tickets')->update(['ticket_source' => 'online']);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['ticket_source']);
            $table->dropIndex(['batch_reference']);
            $table->dropColumn(['ticket_source', 'batch_reference']);
        });
    }
};
