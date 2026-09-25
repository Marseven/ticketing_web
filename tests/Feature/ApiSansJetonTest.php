<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une route d'API appelée sans jeton doit répondre 401, jamais 500.
 *
 * L'application n'a aucune route nommée `login`. Or le middleware
 * d'authentification, face à une requête qui attend du HTML — ce que fait un
 * navigateur quand on colle une URL dans la barre d'adresse —, tente de
 * rediriger vers `route('login')`. La route n'existant pas, l'exception
 * remonte et Laravel rend une erreur 500.
 *
 * Le symptôme est trompeur au possible : un administrateur ouvre l'URL d'un
 * export, obtient « 500 Server Error », et conclut que l'export est cassé. Il
 * l'est d'autant plus que rien n'apparaît dans les journaux applicatifs sous le
 * nom de la fonctionnalité — l'erreur parle d'une route de connexion.
 */
class ApiSansJetonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function routesProtegees(): array
    {
        return [
            'export analytique' => ['/api/v1/admin/analytics/export/events'],
            'liste des utilisateurs' => ['/api/v1/admin/users'],
            'espace organisateur' => ['/api/v1/organizer/dashboard'],
            'profil' => ['/api/v1/auth/me'],
        ];
    }

    /**
     * @dataProvider routesProtegees
     */
    public function test_un_navigateur_sans_jeton_obtient_un_refus_et_non_une_panne(string $url): void
    {
        // `Accept: text/html` reproduit exactement une URL collée dans la barre
        // d'adresse. C'est le cas qui produisait un 500.
        $reponse = $this->get($url, ['Accept' => 'text/html']);

        $this->assertNotSame(500, $reponse->status(),
            "coller {$url} dans un navigateur ne doit pas provoquer d'erreur serveur");
        $this->assertSame(401, $reponse->status());
    }

    /**
     * @dataProvider routesProtegees
     */
    public function test_un_appel_json_sans_jeton_repond_toujours_401(string $url): void
    {
        // Le cas qui fonctionnait déjà : il ne doit pas régresser.
        $this->getJson($url)->assertStatus(401);
    }
}
