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
        Schema::create('organizer_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // airtelmoney, moovmoney, etc.
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('pending_balance', 15, 2)->default(0); // Montants en attente de payout
            $table->string('phone_number')->nullable(); // Numéro pour les payouts
            $table->boolean('auto_payout_enabled')->default(false);
            $table->decimal('auto_payout_threshold', 15, 2)->default(0); // Seuil minimum pour payout auto
            $table->timestamps();
            
            $table->unique(['organizer_id', 'gateway']);
            $table->index(['organizer_id', 'gateway']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_balances');
    }
};