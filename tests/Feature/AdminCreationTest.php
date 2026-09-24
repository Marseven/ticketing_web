<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Création d'un administrateur depuis l'espace d'administration.
 *
 * Un compte créé n'apparaissait pas dans la liste : la création posait le
 * RÔLE, la liste filtrait sur le TYPE d'utilisateur. Le compte existait bel et
 * bien, mais restait invisible.
 */
class AdminCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);
        UserType::firstOrCreate(['code' => 'client'], ['name' => 'client', 'label' => 'Client']);

        foreach ([Role::ADMIN, Role::ORGANIZER, Role::CLIENT] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug]);
        }

        Sanctum::actingAs($this->admin());
    }

    private function admin(): User
    {
        $user = User::create([
            'name' => 'Patron', 'email' => 'patron-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = UserType::where('name', 'admin')->value('id');
        $user->save();
        $user->roles()->syncWithoutDetaching([Role::where('slug', Role::ADMIN)->value('id')]);

        return $user->fresh('roles');
    }

    private function create(array $extra = [])
    {
        return $this->postJson('/api/v1/admin/users', array_merge([
            'name' => 'Nouvel Admin',
            'email' => 'nouvel@primea.test',
            'is_admin' => true,
        ], $extra));
    }

    private function listed(): array
    {
        return collect($this->getJson('/api/v1/admin/users/admins')->assertOk()->json('data.data'))
            ->pluck('email')
            ->all();
    }

    public function test_a_created_admin_appears_in_the_list(): void
    {
        Notification::fake();

        $this->create()->assertSuccessful();

        $this->assertContains('nouvel@primea.test', $this->listed());
    }

    public function test_it_receives_the_admin_user_type(): void
    {
        Notification::fake();

        $this->create()->assertSuccessful();

        $this->assertSame('admin', User::where('email', 'nouvel@primea.test')->first()->userType->name);
    }

    public function test_an_admin_holding_only_the_role_is_listed_too(): void
    {
        // Les comptes créés avant le correctif : rôle posé, type absent.
        $ancien = User::create([
            'name' => 'Ancien', 'email' => 'ancien@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $ancien->roles()->syncWithoutDetaching([Role::where('slug', Role::ADMIN)->value('id')]);

        $this->assertContains('ancien@primea.test', $this->listed());
    }

    public function test_a_chosen_password_works_straight_away(): void
    {
        Notification::fake();

        $this->create([
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
        ])->assertSuccessful();

        $user = User::where('email', 'nouvel@primea.test')->first();

        $this->assertTrue(Hash::check('MotDePasse1!', $user->password));

        // Rien à envoyer : le compte est utilisable immédiatement.
        Notification::assertNothingSent();
    }

    public function test_without_a_password_a_link_is_sent(): void
    {
        Notification::fake();

        $this->create()->assertSuccessful();

        Notification::assertSentTo(
            User::where('email', 'nouvel@primea.test')->first(),
            \App\Notifications\PasswordResetNotification::class
        );
    }

    public function test_a_password_that_is_too_short_is_refused(): void
    {
        $this->create(['password' => 'court', 'password_confirmation' => 'court'])
            ->assertStatus(422);
    }

    public function test_a_mistyped_confirmation_is_refused(): void
    {
        $this->create(['password' => 'MotDePasse1!', 'password_confirmation' => 'AutreChose1!'])
            ->assertStatus(422);
    }

    // ---------------------------------------------------------------
    //  Modification
    // ---------------------------------------------------------------

    private function target(): User
    {
        $user = User::create([
            'name' => 'Leofa Abila', 'email' => 'leofa@primea.test',
            'password' => bcrypt('AncienMotDePasse1!'), 'status' => 'active',
        ]);
        $user->user_type_id = UserType::where('name', 'admin')->value('id');
        $user->save();
        $user->roles()->syncWithoutDetaching([Role::where('slug', Role::ADMIN)->value('id')]);

        return $user->fresh('roles');
    }

    private function update(User $user, array $payload)
    {
        return $this->putJson('/api/v1/admin/users/' . $user->id, array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => true,
        ], $payload));
    }

    public function test_the_password_can_be_changed_from_the_edit_form(): void
    {
        $user = $this->target();

        $this->update($user, [
            'password' => 'NouveauMotDePasse1!',
            'password_confirmation' => 'NouveauMotDePasse1!',
        ])->assertSuccessful();

        $this->assertTrue(Hash::check('NouveauMotDePasse1!', $user->fresh()->password));
    }

    public function test_leaving_the_password_empty_changes_nothing(): void
    {
        $user = $this->target();

        $this->update($user, ['name' => 'Leofa A.'])->assertSuccessful();

        $this->assertTrue(Hash::check('AncienMotDePasse1!', $user->fresh()->password));
        $this->assertSame('Leofa A.', $user->fresh()->name);
    }

    public function test_changing_the_password_closes_open_sessions(): void
    {
        // Changer le mot de passe de quelqu'un sert souvent à lui couper
        // l'accès : le laisser connecté viderait le geste de son sens.
        $user = $this->target();
        $user->createToken('session-ouverte');

        $this->assertSame(1, $user->tokens()->count());

        $this->update($user, [
            'password' => 'NouveauMotDePasse1!',
            'password_confirmation' => 'NouveauMotDePasse1!',
        ])->assertSuccessful();

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_editing_does_not_strip_the_super_admin_role(): void
    {
        // Tout détacher faisait perdre la supervision à un super
        // administrateur pour une simple correction de nom.
        $super = Role::firstOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            ['name' => 'Super Admin', 'level' => 100]
        );

        $user = $this->target();
        $user->roles()->syncWithoutDetaching([$super->id]);

        $this->update($user, ['name' => 'Leofa Abila junior'])->assertSuccessful();

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_the_status_chosen_in_the_form_is_applied(): void
    {
        // L'écran proposait le statut, le serveur ne le lisait pas : le
        // changement disparaissait sans un mot.
        $user = $this->target();

        $this->update($user, ['status' => 'suspended'])->assertSuccessful();

        $this->assertSame('suspended', $user->fresh()->status);
    }

    public function test_a_short_password_is_refused_on_edit(): void
    {
        $user = $this->target();

        $this->update($user, ['password' => 'court', 'password_confirmation' => 'court'])
            ->assertStatus(422);

        $this->assertTrue(Hash::check('AncienMotDePasse1!', $user->fresh()->password));
    }

    public function test_an_admin_holding_only_the_role_is_seen_as_admin_at_login(): void
    {
        // Le compte créé avant le correctif : rôle posé, type absent. Il
        // s'affichait « Client » à la connexion et perdait l'accès à
        // l'administration, alors que le serveur l'y autorisait.
        $ancien = User::create([
            'name' => 'Leofa Abila', 'email' => 'leofa@primea.test',
            'password' => bcrypt('MotDePasse1!'), 'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $ancien->roles()->syncWithoutDetaching([Role::where('slug', Role::ADMIN)->value('id')]);

        Sanctum::actingAs($ancien->fresh('roles'));

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('is_admin', true);
    }

    public function test_editing_an_admin_repairs_a_missing_user_type(): void
    {
        $ancien = User::create([
            'name' => 'Leofa Abila', 'email' => 'leofa2@primea.test',
            'password' => bcrypt('MotDePasse1!'), 'status' => 'active',
        ]);
        $ancien->roles()->syncWithoutDetaching([Role::where('slug', Role::ADMIN)->value('id')]);

        $this->assertNull($ancien->userType);

        $this->putJson('/api/v1/admin/users/' . $ancien->id, [
            'name' => 'Leofa Abila',
            'email' => 'leofa2@primea.test',
            'is_admin' => true,
        ])->assertSuccessful();

        $this->assertSame('admin', $ancien->fresh()->userType?->name);
    }

    public function test_a_password_is_never_stored_in_clear(): void
    {
        Notification::fake();

        $this->create(['password' => 'MotDePasse1!', 'password_confirmation' => 'MotDePasse1!']);

        $this->assertNotSame(
            'MotDePasse1!',
            User::where('email', 'nouvel@primea.test')->first()->password
        );
    }
}
