<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Frais de service (e-billing) figés sur la commande.
     *
     * - service_fee_amount : montant des frais AJOUTÉS au total payé par le
     *   client (0 si la plateforme supporte les frais).
     * - service_fee_bearer : qui a supporté les frais pour cette commande
     *   ('customer' | 'platform'), copié depuis l'événement au moment de l'achat.
     *
     * Le net reversé à l'organisateur (subtotal_amount) n'est PAS impacté :
     * les frais de service concernent uniquement le client et la plateforme.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('service_fee_amount', 12, 2)->default(0)->after('tax_amount');
            $table->string('service_fee_bearer')->nullable()->after('service_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_fee_amount', 'service_fee_bearer']);
        });
    }
};
