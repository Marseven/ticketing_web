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
        Schema::table('venues', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('country')->default('Gabon')->after('postal_code');
            $table->integer('capacity')->nullable()->after('country');
            $table->string('phone', 30)->nullable()->after('capacity');
            $table->string('email')->nullable()->after('phone');
            $table->string('image')->nullable()->after('email');
            $table->string('status')->default('active')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'postal_code', 'country', 'capacity', 
                'phone', 'email', 'image', 'status'
            ]);
        });
    }
};