<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le serveur ne va chercher que des adresses publiquement routables.
 *
 * `POST /images/validate-url` fait faire au serveur une requête vers l'adresse
 * fournie. La règle `url` de Laravel dit seulement qu'elle est bien formée :
 * elle accepte la boucle locale, les réseaux privés et l'adresse de métadonnées
 * des hébergeurs cloud. N'importe quel compte client — l'inscription est
 * ouverte — pouvait donc se servir du serveur comme d'un relais vers ce qu'il
 * ne peut pas joindre, et lire dans la réponse si la cible existe.
 */
class UrlFetchGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::create([
            'name' => 'Client', 'email' => 'c-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]));
    }

    private function check(string $url)
    {
        return $this->postJson('/api/v1/images/validate-url', ['url' => $url, 'type' => 'events']);
    }

    public static function adressesInternes(): array
    {
        return [
            'boucle locale' => ['http://127.0.0.1/image.png'],
            'boucle locale nommée' => ['http://localhost/image.png'],
            'réseau privé 10/8' => ['http://10.0.0.5/image.png'],
            'réseau privé 192.168' => ['http://192.168.1.1/image.png'],
            'réseau privé 172.16' => ['http://172.16.0.1/image.png'],
            'métadonnées cloud' => ['http://169.254.169.254/latest/meta-data/'],
            'base de données locale' => ['http://127.0.0.1:3306/'],
        ];
    }

    /** @dataProvider adressesInternes */
    public function test_an_internal_address_is_refused(string $url): void
    {
        Http::fake();

        $this->check($url)->assertStatus(422);

        // Le point capital : aucune requête n'est partie. Refuser après coup
        // laisserait déjà fuiter l'existence de la cible.
        Http::assertNothingSent();
    }

    public function test_a_non_http_scheme_is_refused(): void
    {
        Http::fake();

        foreach (['ftp://example.com/x.png', 'file:///etc/passwd', 'gopher://example.com/'] as $url) {
            $this->check($url)->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_a_public_image_is_still_accepted(): void
    {
        // La fonctionnalité doit continuer de rendre service.
        Http::fake(['*' => Http::response('', 200, ['Content-Type' => 'image/png'])]);

        $this->check('https://example.com/affiche.png')->assertOk();
    }

    public function test_a_public_address_that_is_not_an_image_is_refused(): void
    {
        Http::fake(['*' => Http::response('', 200, ['Content-Type' => 'text/html'])]);

        $this->check('https://example.com/page.html')->assertStatus(400);
    }

    public function test_redirects_are_not_followed(): void
    {
        // Sans cela, une adresse publique renvoyant vers la boucle locale
        // ferait retomber dans le cas qu'on vient d'écarter.
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/'])]);

        $this->check('https://example.com/redirige')->assertStatus(400);
    }
}
