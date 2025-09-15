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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('type')->default('system'); // system, organizer, custom
            $table->boolean('is_active')->default(true);
            $table->integer('level')->default(0); // Niveau de priorité pour hiérarchie
            $table->json('permissions')->nullable(); // Permissions spécifiques au rôle
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
