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
     * Extend banners.position enum to include 'header-top' (full-width ad
     * banner above the site header) and align with the values already
     * accepted by the admin validator (home-top, home-bottom).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't enforce enum constraints — no schema change needed.
            return;
        }

        DB::statement("ALTER TABLE banners MODIFY COLUMN position ENUM('home', 'home-top', 'home-bottom', 'header-top', 'events', 'checkout', 'all') DEFAULT 'home'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("UPDATE banners SET position = 'home' WHERE position IN ('home-top', 'home-bottom', 'header-top')");
        DB::statement("ALTER TABLE banners MODIFY COLUMN position ENUM('home', 'events', 'checkout', 'all') DEFAULT 'home'");
    }
};
