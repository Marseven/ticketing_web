<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rôle « super administrateur ».
 *
 * La supervision de la plateforme donne à voir l'état technique, la
 * fréquentation et les flux financiers en cours. C'est un cran au-dessus de
 * l'administration courante : tout le monde n'a pas à y accéder.
 *
 * ⚠️ Les administrateurs actuels reçoivent ce rôle, sinon personne ne
 * pourrait ouvrir la page le jour du déploiement. À l'exploitant de le
 * retirer à qui n'en a pas besoin :
 *
 *     php artisan admin:super --revoke quelquun@example.com
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $role = Role::firstOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => Role::SUPER_ADMIN,
                'description' => 'Supervision de la plateforme et administration complète',
                'level' => 100,
            ]
        );

        $admins = User::whereHas('roles', fn ($q) => $q->where('slug', Role::ADMIN))->get();

        foreach ($admins as $admin) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $role = Role::where('slug', Role::SUPER_ADMIN)->first();

        if ($role) {
            DB::table('role_user')->where('role_id', $role->id)->delete();
            $role->delete();
        }
    }
};
