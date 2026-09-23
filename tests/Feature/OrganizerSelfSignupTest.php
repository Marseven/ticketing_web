<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inscription autonome comme organisateur.
 *
 * Les comptes organisateurs sont ouverts par l'administration, après échange
 * avec la personne. Masquer les liens dans l'interface ne suffit pas :
 * l'inscription est un appel d'API public, que n'importe qui peut faire
 * directement. Le refus est donc posé côté serveur.
 */
class OrganizerSelfSignupTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $extra = [])
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'Nouvel Arrivant',
            'email' => 'arrivant-' . uniqid() . '@primea.test',
            'password' => 'MotDePasse1!',
            'password_confirmation' => 'MotDePasse1!',
            'phone' => '062000000',
        ], $extra));
    }

    public function test_asking_to_be_an_organizer_creates_a_client_account(): void
    {
        config(['features.organizer_self_signup' => false]);

        $this->register(['is_organizer' => true])->assertSuccessful();

        $user = User::latest('id')->first();

        $this->assertFalse((bool) $user->is_organizer);
        $this->assertSame(0, Organizer::count(), 'aucune organisation ne doit naître d\'une inscription publique');
    }

    public function test_an_ordinary_registration_still_works(): void
    {
        config(['features.organizer_self_signup' => false]);

        $this->register()->assertSuccessful();

        $this->assertFalse((bool) User::latest('id')->first()->is_organizer);
    }

    public function test_the_door_can_be_reopened_by_configuration(): void
    {
        // Le jour où l'inscription autonome est rouverte, elle doit
        // fonctionner sans toucher au code.
        config(['features.organizer_self_signup' => true]);

        $this->register(['is_organizer' => true, 'organization_name' => 'Cod\'On'])->assertSuccessful();

        $this->assertTrue((bool) User::latest('id')->first()->is_organizer);
        $this->assertSame(1, Organizer::count());
    }

    public function test_the_setting_is_read_from_the_config_file(): void
    {
        // Garde-fou contre la panne déjà vécue sur les paiements : une valeur
        // lue par `env()` hors d'un fichier de config est vide dès que la
        // configuration est mise en cache.
        $source = file_get_contents(app_path('Http/Controllers/Api/AuthController.php'));

        $this->assertStringContainsString("config('features.organizer_self_signup'", $source);
        $this->assertStringNotContainsString("env('ORGANIZER_SELF_SIGNUP'", $source);
    }

    public function test_the_signup_page_no_longer_offers_to_create_an_account(): void
    {
        $page = file_get_contents(resource_path('js/pages/auth/OrganizerChoice.vue'));

        // La connexion reste, pour les organisateurs dont le compte existe.
        $this->assertStringContainsString('/login-organizer', $page);
        $this->assertStringNotContainsString('to="/register-organizer"', $page);
    }

    public function test_no_public_page_links_to_the_organizer_signup(): void
    {
        $surfaces = [
            'js/components/layout/NewFooter.vue',
            'js/components/layout/NewHeader.vue',
            'js/pages/Home.vue',
            'js/pages/static/HowItWorks.vue',
        ];

        foreach ($surfaces as $surface) {
            $source = file_get_contents(resource_path($surface));

            $this->assertStringNotContainsString('to="/organizer-choice"', $source, $surface);
            $this->assertStringNotContainsString("name: 'organizer-choice'", $source, $surface);
        }
    }
}
