<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le résultat d'un check-in était un enum figé (valid/duplicate/invalid).
     * On le passe en chaîne (validée applicativement) pour autoriser la valeur
     * d'audit « reset » (réinitialisation admin d'un billet scanné).
     */
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->string('result', 20)->default('valid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->enum('result', ['valid', 'duplicate', 'invalid'])->default('valid')->change();
        });
    }
};
