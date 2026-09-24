<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Révocation des sessions ouvertes.
 *
 * L'écran de profil proposait « révoquer » et « déconnecter partout » alors
 * qu'aucune route ne répondait : il annonçait un succès sans rien fermer. Un
 * administrateur qui soupçonne une intrusion croyait donc avoir coupé les
 * accès en les laissant ouverts.
 */
class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'name' => 'Utilisateur', 'email' => 'u-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
    }

    private function as(User $user): string
    {
        return $user->createToken('session')->plainTextToken;
    }

    public function test_other_sessions_are_really_closed(): void
    {
        $user = $this->user();
        $this->as($user);
        $this->as($user);
        $current = $this->as($user);

        $this->assertSame(3, $user->tokens()->count());

        $this->withToken($current)
            ->postJson('/api/v1/profile/sessions/logout-others')
            ->assertOk()
            ->assertJsonPath('data.revoked', 2);

        $this->assertSame(1, $user->fresh()->tokens()->count(), 'seule la session courante survit');
    }

    public function test_the_current_session_keeps_working_afterwards(): void
    {
        $user = $this->user();
        $this->as($user);
        $current = $this->as($user);

        $this->withToken($current)->postJson('/api/v1/profile/sessions/logout-others')->assertOk();
        $this->withToken($current)->getJson('/api/v1/profile/sessions')->assertOk();
    }

    public function test_nobody_can_revoke_someone_else_session(): void
    {
        // Filtrer par la relation et non par l'identifiant seul : autrement il
        // suffisait de changer un chiffre dans l'URL.
        $victim = $this->user();
        $victimToken = $victim->createToken('session');

        $attacker = $this->user();

        $this->withToken($this->as($attacker))
            ->deleteJson('/api/v1/profile/sessions/' . $victimToken->accessToken->id)
            ->assertStatus(404);

        $this->assertSame(1, $victim->fresh()->tokens()->count());
    }

    public function test_the_current_session_is_not_revoked_by_mistake(): void
    {
        $user = $this->user();
        $token = $user->createToken('session');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/profile/sessions/' . $token->accessToken->id)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'CURRENT_SESSION');
    }

    public function test_a_named_session_is_revoked(): void
    {
        $user = $this->user();
        $other = $user->createToken('telephone');
        $current = $this->as($user);

        $this->withToken($current)
            ->deleteJson('/api/v1/profile/sessions/' . $other->accessToken->id)
            ->assertOk();

        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_the_listing_marks_the_current_session(): void
    {
        $user = $this->user();
        $user->createToken('telephone');
        $current = $user->createToken('navigateur');

        $rows = $this->withToken($current->plainTextToken)
            ->getJson('/api/v1/profile/sessions')->assertOk()->json('data');

        $this->assertCount(2, $rows);
        $this->assertSame(1, collect($rows)->where('current', true)->count());
    }
}
