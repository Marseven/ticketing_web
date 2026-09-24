<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Double authentification par code temporaire (Google Authenticator).
 *
 * Le point capital : quand le 2FA est actif, la connexion par mot de passe ne
 * crée AUCUNE session. Elle rend un défi, et le jeton n'est délivré qu'après
 * un code valide. Émettre le jeton puis « demander » le code donnerait un
 * accès complet à qui intercepte la première réponse — le second facteur ne
 * serait plus qu'une formalité.
 */
class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    private function user(string $password = 'mot-de-passe'): User
    {
        return User::create([
            'name' => 'Utilisateur',
            'email' => 'u-' . uniqid() . '@primea.test',
            'password' => bcrypt($password),
            'status' => 'active',
        ]);
    }

    private function withTwoFactor(string $password = 'mot-de-passe'): array
    {
        $user = $this->user($password);
        $secret = $this->totp->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secret];
    }

    private function login(User $user, string $password = 'mot-de-passe')
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => $password,
        ]);
    }

    // ---------------------------------------------------------------
    //  La session n'existe pas avant le second facteur
    // ---------------------------------------------------------------

    public function test_a_password_alone_opens_no_session_when_two_factor_is_on(): void
    {
        [$user] = $this->withTwoFactor();

        $response = $this->login($user);

        $response->assertOk()
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonMissingPath('token');

        $this->assertSame(0, $user->tokens()->count(), 'aucun jeton n\'est créé avant le code');
    }

    public function test_the_session_opens_once_the_code_is_given(): void
    {
        [$user, $secret] = $this->withTwoFactor();

        $challenge = $this->login($user)->json('challenge');

        $response = $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $challenge,
            'code' => $this->totp->codeAt($secret),
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('token'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_wrong_code_opens_nothing(): void
    {
        [$user] = $this->withTwoFactor();
        $challenge = $this->login($user)->json('challenge');

        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $challenge,
            'code' => '000000',
        ])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CODE');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_the_challenge_burns_after_five_attempts(): void
    {
        // Six chiffres se devinent vite si l'on peut essayer sans fin.
        [$user] = $this->withTwoFactor();
        $challenge = $this->login($user)->json('challenge');

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/auth/two-factor/challenge', [
                'challenge' => $challenge, 'code' => '000000',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $challenge, 'code' => '000000',
        ])->assertStatus(429)->assertJsonPath('error_code', 'CHALLENGE_BURNED');

        // Le défi est détruit : même le bon code ne le rouvre plus.
        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $challenge, 'code' => '000000',
        ])->assertStatus(422)->assertJsonPath('error_code', 'CHALLENGE_EXPIRED');
    }

    public function test_an_unknown_challenge_is_refused(): void
    {
        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => str_repeat('x', 64), 'code' => '123456',
        ])->assertStatus(422)->assertJsonPath('error_code', 'CHALLENGE_EXPIRED');
    }

    public function test_a_user_without_two_factor_logs_in_as_before(): void
    {
        // La connexion ordinaire ne doit pas changer d'un iota.
        $user = $this->user();

        $response = $this->login($user);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('token'));
    }

    // ---------------------------------------------------------------
    //  Codes de secours
    // ---------------------------------------------------------------

    public function test_a_recovery_code_opens_the_session_and_is_consumed(): void
    {
        [$user] = $this->withTwoFactor();
        $challenge = $this->login($user)->json('challenge');

        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $challenge, 'recovery_code' => 'AAAAA-BBBBB',
        ])->assertOk();

        $this->assertSame(['CCCCC-DDDDD'], array_values($user->fresh()->two_factor_recovery_codes),
            'un code de secours ne sert qu\'une fois');
    }

    public function test_a_spent_recovery_code_no_longer_opens(): void
    {
        [$user] = $this->withTwoFactor();

        $first = $this->login($user)->json('challenge');
        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $first, 'recovery_code' => 'AAAAA-BBBBB',
        ])->assertOk();

        $second = $this->login($user)->json('challenge');
        $this->postJson('/api/v1/auth/two-factor/challenge', [
            'challenge' => $second, 'recovery_code' => 'AAAAA-BBBBB',
        ])->assertStatus(422);
    }

    // ---------------------------------------------------------------
    //  Activation
    // ---------------------------------------------------------------

    public function test_generating_a_secret_does_not_enable_anything_yet(): void
    {
        // Activer dès le QR enfermerait dehors qui le scanne mal.
        $user = $this->user();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile/two-factor');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.secret'));
        $this->assertNotEmpty($response->json('data.otpauth_uri'));
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_a_valid_first_code_enables_it_and_hands_over_recovery_codes(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $secret = $this->postJson('/api/v1/profile/two-factor')->json('data.secret');

        $response = $this->postJson('/api/v1/profile/two-factor/confirm', [
            'code' => $this->totp->codeAt($secret),
        ]);

        $response->assertOk();
        $this->assertCount(8, $response->json('data.recovery_codes'));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_a_wrong_first_code_leaves_it_off(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/profile/two-factor');

        $this->postJson('/api/v1/profile/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422);

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_disabling_requires_the_password(): void
    {
        // Sans cette preuve, un jeton volé désarmerait la protection censée
        // le rendre inoffensif.
        [$user] = $this->withTwoFactor();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile/two-factor', ['password' => 'pas-le-bon'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_PASSWORD');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->deleteJson('/api/v1/profile/two-factor', ['password' => 'mot-de-passe'])
            ->assertOk();

        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_the_secret_never_leaves_in_a_user_payload(): void
    {
        // Qui lit le secret génère les codes : il vaut le mot de passe.
        [$user] = $this->withTwoFactor();

        $this->assertArrayNotHasKey('two_factor_secret', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->fresh()->toArray());
    }
}
