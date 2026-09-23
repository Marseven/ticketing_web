<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime le rôle « super-admin » créé par erreur.
 *
 * Le projet possédait déjà un rôle « super_admin » (avec un underscore), créé
 * par le RoleSeeder. Une migration précédente en a créé un second, écrit avec
 * un tiret, et a pu l'attribuer à des comptes : deux rôles pour une même
 * notion, dont un seul est reconnu par le reste du code.
 *
 * On rapatrie donc les éventuels titulaires sur le rôle légitime, puis on
 * efface le doublon.
 */
return new class extends Migration
{
    private const STRAY = 'super-admin';

    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_user')) {
            return;
        }

        $stray = Role::where('slug', self::STRAY)->first();

        if (! $stray) {
            return;
        }

        $legitimate = Role::where('slug', Role::SUPER_ADMIN)->first();

        if ($legitimate) {
            $holders = DB::table('role_user')->where('role_id', $stray->id)->pluck('user_id');

            foreach ($holders as $userId) {
                $alreadyHas = DB::table('role_user')
                    ->where('role_id', $legitimate->id)
                    ->where('user_id', $userId)
                    ->exists();

                if (! $alreadyHas) {
                    DB::table('role_user')->insert([
                        'role_id' => $legitimate->id,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::table('role_user')->where('role_id', $stray->id)->delete();
        $stray->delete();
    }

    public function down(): void
    {
        // Le doublon n'avait pas lieu d'être : on ne le recrée pas.
    }
};
