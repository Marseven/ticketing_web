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
     * 2025_10_08 already standardized orders + ticket_types from XOF to XAF
     * but missed ticket_prices. This migration brings it in line.
     */
    public function up(): void
    {
        if (Schema::hasTable('ticket_prices')) {
            DB::table('ticket_prices')->where('currency', 'XOF')->update(['currency' => 'XAF']);

            Schema::table('ticket_prices', function (Blueprint $table) {
                $table->string('currency', 3)->default('XAF')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ticket_prices')) {
            DB::table('ticket_prices')->where('currency', 'XAF')->update(['currency' => 'XOF']);

            Schema::table('ticket_prices', function (Blueprint $table) {
                $table->string('currency', 3)->default('XOF')->change();
            });
        }
    }
};
