<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * S'assure que le rôle « super administrateur » existe.
 *
 * Ce rôle n'est pas nouveau : le RoleSeeder le crée depuis l'origine
 * (« Super Admin », niveau 100, accès complet au système). Cette migration ne
 * fait que garantir sa présence sur une installation où le seeder n'aurait
 * pas tourné, avec les mêmes attributs.
 *
 * ⚠️ Elle n'accorde le rôle à personne. Un administrateur ordinaire n'est pas
 * un super administrateur : le lui donner d'office élargirait ses droits sans
 * que personne ne l'ait demandé. Pour voir qui le détient, ou l'accorder :
 *
 *     php artisan admin:super --list
 *     php artisan admin:super quelquun@example.com
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $adminTypeId = \App\Models\UserType::where('name', 'admin')->value('id');

        Role::firstOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            array_filter([
                'name' => 'Super Admin',
                'description' => 'Accès complet au système avec tous les privilèges',
                'type' => Role::TYPE_SYSTEM,
                'user_type_id' => $adminTypeId,
                'level' => 100,
            ], fn ($value) => $value !== null)
        );
    }

    public function down(): void
    {
        // Rien : le rôle préexistait à cette migration, le supprimer
        // retirerait des droits que cette migration n'a pas donnés.
    }
};
