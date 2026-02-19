<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add 'pending' to tickets.status enum and make issued_at nullable.
     * Tickets are created as 'pending' at order time, then switched to 'issued'
     * when payment is confirmed via webhook.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN - recreate workaround not needed
            // since SQLite doesn't enforce enum constraints
            Schema::table('tickets', function (Blueprint $table) {
                $table->datetime('issued_at')->nullable()->change();
            });
        } else {
            // MySQL: modify enum and make issued_at nullable
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('pending', 'issued', 'used', 'refunded', 'void') DEFAULT 'pending'");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN issued_at DATETIME NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('tickets', function (Blueprint $table) {
                $table->datetime('issued_at')->nullable(false)->change();
            });
        } else {
            // Revert: remove 'pending' from enum, restore NOT NULL
            DB::statement("UPDATE tickets SET status = 'issued' WHERE status = 'pending'");
            DB::statement("UPDATE tickets SET issued_at = NOW() WHERE issued_at IS NULL");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('issued', 'used', 'refunded', 'void') DEFAULT 'issued'");
            DB::statement("ALTER TABLE tickets MODIFY COLUMN issued_at DATETIME NOT NULL");
        }
    }
};
