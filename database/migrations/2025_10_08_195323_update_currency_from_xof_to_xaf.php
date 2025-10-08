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
        // Mettre à jour toutes les devises XOF vers XAF dans orders
        \DB::table('orders')->where('currency', 'XOF')->update(['currency' => 'XAF']);

        // Mettre à jour toutes les devises XOF vers XAF dans ticket_types
        \DB::table('ticket_types')->where('currency', 'XOF')->update(['currency' => 'XAF']);

        // Changer la valeur par défaut dans orders
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('XAF')->change();
        });

        // Changer la valeur par défaut dans ticket_types
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('currency', 3)->default('XAF')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer XOF
        \DB::table('orders')->where('currency', 'XAF')->update(['currency' => 'XOF']);
        \DB::table('ticket_types')->where('currency', 'XAF')->update(['currency' => 'XOF']);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('XOF')->change();
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->string('currency', 3)->default('XOF')->change();
        });
    }
};
