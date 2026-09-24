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
