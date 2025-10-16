<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_recurrence_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])->default('weekly');
            $table->integer('interval')->default(1)->comment('Every X days/weeks/months');
            $table->string('by_day')->nullable()->comment('Jours semaine: MO,TU,WE,TH,FR,SA,SU');
            $table->string('by_month_day')->nullable()->comment('Jours du mois: 1,15,30');
            $table->string('by_month')->nullable()->comment('Mois: 1,6,12');
            $table->integer('count')->nullable()->comment('Nombre d\'occurrences');
            $table->datetime('until')->nullable()->comment('Date de fin de récurrence');
            $table->json('exceptions')->nullable()->comment('Dates à exclure');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->index(['event_id']);
            $table->index(['frequency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_recurrence_rules');
    }
};
