<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Colonnes de correspondance pour l'import des données legacy MyTicketO
     * (bases leweb_*). `legacy_id` garde l'ancien identifiant pour recâbler
     * les FK et rendre l'import idempotent (upsert). `legacy_md5` conserve
     * le hachage MD5 legacy le temps d'un rehash bcrypt transparent au 1er
     * login (cf. AuthController).
     */
    public function up(): void
    {
        $tables = ['organizers', 'users', 'events', 'event_schedules', 'ticket_types', 'orders', 'tickets'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'legacy_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('legacy_id')->nullable()->index();
                });
            }
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'legacy_md5')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('legacy_md5', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        $tables = ['organizers', 'users', 'events', 'event_schedules', 'ticket_types', 'orders', 'tickets'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'legacy_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('legacy_id');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'legacy_md5')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropColumn('legacy_md5');
            });
        }
    }
};
