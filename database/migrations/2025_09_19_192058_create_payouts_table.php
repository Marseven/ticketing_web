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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique(); // Notre référence interne
            $table->string('external_reference')->unique(); // Référence envoyée à SHAP
            $table->string('gateway'); // airtelmoney, moovmoney, etc.
            $table->string('payment_system_name'); // Nom du système pour SHAP API
            $table->string('payee_msisdn'); // Numéro de téléphone du bénéficiaire
            $table->decimal('amount', 15, 2);
            $table->string('payout_type')->default('withdrawal'); // withdrawal, refund, cashback
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled'])->default('pending');
            $table->boolean('is_automatic')->default(false); // Payout automatique ou manuel
            $table->json('shap_response')->nullable(); // Réponse de l'API SHAP
            $table->string('shap_payout_id')->nullable(); // ID du payout dans SHAP
            $table->string('shap_transaction_id')->nullable(); // ID de transaction SHAP
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['organizer_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('external_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};