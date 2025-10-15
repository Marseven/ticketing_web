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
        // Rendre buyer_id nullable dans la table orders
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
        });

        // Rendre buyer_id nullable dans la table tickets
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre buyer_id non nullable dans la table orders
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable(false)->change();
        });

        // Remettre buyer_id non nullable dans la table tickets
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable(false)->change();
        });
    }
};
