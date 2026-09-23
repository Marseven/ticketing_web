<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le rôle de super administrateur est celui qui existait déjà.
 *
 * Ce projet possède un rôle « super_admin » depuis son RoleSeeder. Une
 * migration en a créé un second, écrit « super-admin » : deux rôles pour une
 * même notion, dont un seul reconnu par le code. Un compte réellement super
 * administrateur se voyait alors refuser la supervision, et
 * « admin:super --list » annonçait que personne ne détenait le rôle.
 */
class SuperAdminRoleIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_slug_is_the_one_the_project_already_used(): void
    {
        // Underscore, comme dans RoleSeeder et dans l'écran des administrateurs.
        $this->assertSame('super_admin', Role::SUPER_ADMIN);
    }

    public function test_a_holder_of_the_seeded_role_is_recognised(): void
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'description' => 'Accès complet', 'level' => 100]
        );

        $user = User::create([
            'name' => 'Chef', 'email' => 'chef@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_the_stray_role_is_merged_and_removed(): void
    {
        $legitimate = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'level' => 100]
        );

        $stray = Role::create(['slug' => 'super-admin', 'name' => 'Doublon', 'level' => 100]);

        $user = User::create([
            'name' => 'Ancien', 'email' => 'ancien@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$stray->id]);

        (require database_path('migrations/2026_09_23_120200_merge_stray_super_admin_role.php'))->up();

        // Le titulaire est rapatrié sur le rôle légitime, le doublon disparaît.
        $this->assertTrue($user->fresh()->isSuperAdmin());
        $this->assertNull(Role::where('slug', 'super-admin')->first());
        $this->assertSame(0, DB::table('role_user')->where('role_id', $stray->id)->count());
    }

    public function test_the_merge_does_nothing_when_there_is_no_stray_role(): void
    {
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'level' => 100]);

        (require database_path('migrations/2026_09_23_120200_merge_stray_super_admin_role.php'))->up();

        $this->assertNotNull(Role::where('slug', 'super_admin')->first());
    }

    public function test_an_ordinary_administrator_is_not_promoted_by_the_migration(): void
    {
        // Donner la supervision à tout administrateur élargirait ses droits
        // sans que personne ne l'ait demandé.
        $admin = Role::firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Admin', 'level' => 90]);

        $user = User::create([
            'name' => 'Admin', 'email' => 'admin@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$admin->id]);

        (require database_path('migrations/2026_09_23_120000_add_super_admin_role.php'))->up();

        $this->assertFalse($user->fresh()->isSuperAdmin());
    }
}
