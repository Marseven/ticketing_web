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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // financial, events, users, performance
            $table->string('period');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->string('format')->default('PDF'); // PDF, Excel
            $table->string('status')->default('pending'); // pending, ready, error
            $table->json('data')->nullable(); // JSON data used to generate the report
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};