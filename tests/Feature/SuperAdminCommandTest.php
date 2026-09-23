<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gestion du rôle de super administrateur.
 *
 * Le rôle ouvre la supervision de la plateforme. Il se donne à la main, un
 * compte à la fois, et la commande dit toujours qui le détient après coup :
 * on ne découvre pas par hasard qui peut voir ces écrans.
 */
class SuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'chef@primea.test'): User
    {
        return User::create([
            'name' => 'Chef', 'email' => $email,
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
    }

    public function test_the_role_can_be_granted(): void
    {
        $user = $this->user();

        $this->artisan('admin:super', ['email' => $user->email])
            ->expectsOutputToContain('Rôle accordé')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_the_role_can_be_revoked(): void
    {
        $user = $this->user();
        $this->artisan('admin:super', ['email' => $user->email]);

        $this->artisan('admin:super', ['email' => $user->email, '--revoke' => true])
            ->expectsOutputToContain('Rôle retiré')
            ->assertSuccessful();

        $this->assertFalse($user->fresh()->isSuperAdmin());
    }

    public function test_granting_twice_is_harmless(): void
    {
        $user = $this->user();

        $this->artisan('admin:super', ['email' => $user->email]);
        $this->artisan('admin:super', ['email' => $user->email])->assertSuccessful();

        $this->assertSame(1, $user->roles()->where('slug', Role::SUPER_ADMIN)->count());
    }

    public function test_an_unknown_address_is_refused(): void
    {
        $this->artisan('admin:super', ['email' => 'personne@example.test'])
            ->expectsOutputToContain('Aucun compte')
            ->assertFailed();
    }

    public function test_it_lists_who_holds_the_role(): void
    {
        $this->artisan('admin:super', ['email' => $this->user('un@primea.test')->email]);
        $this->artisan('admin:super', ['email' => $this->user('deux@primea.test')->email]);

        $this->artisan('admin:super', ['--list' => true])
            ->expectsOutputToContain('un@primea.test')
            ->expectsOutputToContain('deux@primea.test')
            ->assertSuccessful();
    }

    public function test_it_warns_when_nobody_holds_the_role(): void
    {
        // Personne ne peut alors ouvrir la supervision : cela doit se voir.
        Role::firstOrCreate(['slug' => Role::SUPER_ADMIN], ['name' => Role::SUPER_ADMIN, 'level' => 100]);
        User::whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))
            ->get()
            ->each(fn ($u) => $u->roles()->detach());

        $this->artisan('admin:super', ['--list' => true])
            ->expectsOutputToContain('Aucun super administrateur')
            ->assertSuccessful();
    }
}
