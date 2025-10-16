<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Système de tarification flexible permettant :
     * - Prix par défaut au niveau ticket_type
     * - Prix spécifique par schedule (date/horaire)
     * - Prix spécifique par venue (lieu)
     * - Prix spécifique par schedule ET venue
     */
    public function up(): void
    {
        // 1. Créer la table ticket_prices si elle n'existe pas
        if (!Schema::hasTable('ticket_prices')) {
            Schema::create('ticket_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_type_id');
                $table->unsignedBigInteger('schedule_id')->nullable()->comment('Prix pour un schedule spécifique');
                $table->unsignedBigInteger('venue_id')->nullable()->comment('Prix pour un lieu spécifique');
                $table->string('currency', 3)->default('XAF');
                $table->decimal('price', 12, 2)->comment('Prix unitaire');
                $table->datetime('valid_from')->nullable()->comment('Date de début validité');
                $table->datetime('valid_until')->nullable()->comment('Date de fin validité');
                $table->integer('priority')->default(0)->comment('Priorité: plus élevé = appliqué en premier');
                $table->text('description')->nullable()->comment('Description du prix (ex: Early bird, Last minute)');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->foreign('ticket_type_id')->references('id')->on('ticket_types')->onDelete('cascade');
                $table->foreign('schedule_id')->references('id')->on('event_schedules')->onDelete('cascade');
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');

                $table->index(['ticket_type_id']);
                $table->index(['schedule_id']);
                $table->index(['venue_id']);
                $table->index(['status']);
                $table->index(['priority']);
                $table->index(['valid_from', 'valid_until']);
            });
        }

        // 2. Ajouter venue_id à event_schedules pour gérer les événements multi-lieux
        if (!Schema::hasColumn('event_schedules', 'venue_id')) {
            Schema::table('event_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('venue_id')->nullable()->after('event_id')->comment('Lieu spécifique pour ce schedule');
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
                $table->index(['venue_id']);
            });
        }

        // 3. Ajouter champ pour indiquer si l'événement utilise la tarification avancée
        if (!Schema::hasColumn('events', 'use_variable_pricing')) {
            Schema::table('events', function (Blueprint $table) {
                $table->boolean('use_variable_pricing')->default(false)->after('status')
                    ->comment('Si true, utilise ticket_prices, sinon prix du ticket_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes ajoutées
        if (Schema::hasColumn('events', 'use_variable_pricing')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('use_variable_pricing');
            });
        }

        if (Schema::hasColumn('event_schedules', 'venue_id')) {
            Schema::table('event_schedules', function (Blueprint $table) {
                $table->dropForeign(['venue_id']);
                $table->dropColumn('venue_id');
            });
        }

        // Supprimer la table ticket_prices
        Schema::dropIfExists('ticket_prices');
    }
};
