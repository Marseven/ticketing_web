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
     * La table `ticket_prices` a été créée par la migration initiale avec
     * l'ancien schéma (unit_price / sale_starts_at / sale_ends_at). La
     * migration 2025_10_16 devait la recréer avec le schéma attendu par le
     * modèle (price / valid_from / valid_until / priority / venue_id /
     * description / status) MAIS elle est gardée par `hasTable` et a donc été
     * sautée. Résultat : le moteur de tarification dynamique (TicketPrice +
     * TicketType::getPriceFor) ne correspondait pas au schéma réel.
     *
     * Cette migration aligne la table sur le modèle et recopie les données
     * des anciennes colonnes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ticket_prices')) {
            return;
        }

        Schema::table('ticket_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_prices', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('currency');
            }
            if (!Schema::hasColumn('ticket_prices', 'valid_from')) {
                $table->datetime('valid_from')->nullable()->after('price');
            }
            if (!Schema::hasColumn('ticket_prices', 'valid_until')) {
                $table->datetime('valid_until')->nullable()->after('valid_from');
            }
            if (!Schema::hasColumn('ticket_prices', 'venue_id')) {
                $table->unsignedBigInteger('venue_id')->nullable()->after('schedule_id');
            }
            if (!Schema::hasColumn('ticket_prices', 'priority')) {
                $table->integer('priority')->default(0)->after('valid_until');
            }
            if (!Schema::hasColumn('ticket_prices', 'description')) {
                $table->text('description')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('ticket_prices', 'status')) {
                $table->string('status', 20)->default('active')->after('description');
            }
        });

        // Recopier les données des anciennes colonnes si elles existent.
        if (Schema::hasColumn('ticket_prices', 'unit_price')) {
            DB::statement('UPDATE ticket_prices SET price = unit_price WHERE (price IS NULL OR price = 0)');

            // L'ancienne colonne unit_price est NOT NULL : la rendre nullable
            // pour que les insertions via le modèle (qui n'écrit que `price`)
            // ne violent pas la contrainte. Colonne dépréciée.
            Schema::table('ticket_prices', function (Blueprint $table) {
                $table->decimal('unit_price', 12, 2)->nullable()->change();
            });
        }
        if (Schema::hasColumn('ticket_prices', 'sale_starts_at')) {
            DB::statement('UPDATE ticket_prices SET valid_from = sale_starts_at WHERE valid_from IS NULL');
        }
        if (Schema::hasColumn('ticket_prices', 'sale_ends_at')) {
            DB::statement('UPDATE ticket_prices SET valid_until = sale_ends_at WHERE valid_until IS NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ticket_prices')) {
            return;
        }

        Schema::table('ticket_prices', function (Blueprint $table) {
            foreach (['price', 'valid_from', 'valid_until', 'venue_id', 'priority', 'description', 'status'] as $col) {
                if (Schema::hasColumn('ticket_prices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
