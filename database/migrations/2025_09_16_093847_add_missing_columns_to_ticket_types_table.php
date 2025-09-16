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
        Schema::table('ticket_types', function (Blueprint $table) {
            // Ajouter les colonnes manquantes
            $table->decimal('price', 12, 2)->default(0)->after('description');
            $table->string('currency', 3)->default('XOF')->after('price');
            $table->integer('max_quantity')->nullable()->after('currency');
            $table->integer('min_quantity')->default(1)->after('max_quantity');
            $table->integer('available_quantity')->nullable()->after('min_quantity');
            $table->datetime('sales_start')->nullable()->after('available_quantity');
            $table->datetime('sales_end')->nullable()->after('sales_start');
            $table->integer('sort_order')->default(0)->after('sales_end');
            $table->json('metadata')->nullable()->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn([
                'price',
                'currency', 
                'max_quantity',
                'min_quantity',
                'available_quantity',
                'sales_start',
                'sales_end',
                'sort_order',
                'metadata'
            ]);
        });
    }
};
