<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Double authentification par code temporaire (Google Authenticator).
 *
 * Le secret et les codes de secours sont chiffrés par le modèle (cast
 * `encrypted`) : quiconque obtient une copie de la base — sauvegarde,
 * export, accès mutualisé — ne doit pas pouvoir générer les codes de
 * quelqu'un d'autre.
 *
 * `two_factor_confirmed_at` distingue « secret généré » de « 2FA active ».
 * Sans cette distinction, un compte serait verrouillé dès l'affichage du QR
 * code, avant même que l'utilisateur ait prouvé que son téléphone lit bien
 * le secret — et il ne pourrait plus se connecter pour réparer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
