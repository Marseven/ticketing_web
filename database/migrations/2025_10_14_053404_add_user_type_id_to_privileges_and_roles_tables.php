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
        // Ajouter user_type_id à la table privileges
        Schema::table('privileges', function (Blueprint $table) {
            $table->foreignId('user_type_id')->nullable()->after('id')->constrained('user_types')->onDelete('cascade');
        });

        // Ajouter user_type_id à la table roles
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('user_type_id')->nullable()->after('id')->constrained('user_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('privileges', function (Blueprint $table) {
            $table->dropForeign(['user_type_id']);
            $table->dropColumn('user_type_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['user_type_id']);
            $table->dropColumn('user_type_id');
        });
    }
};
