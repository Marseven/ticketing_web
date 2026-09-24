<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Qui a le droit d'appeler quoi.
 *
 * Les contrôles d'accès sont dispersés : middlewares, gardes en tête de
 * contrôleur, vérifications d'appartenance. Chacun est correct isolément, et
 * c'est justement pourquoi une brèche passe inaperçue — il suffit qu'un point
 * d'entrée ajouté plus tard oublie sa garde.
 *
 * Cette matrice énumère les points sensibles et vérifie, pour chaque profil,
 * qu'il obtient bien un refus. Elle ne teste pas ce que font ces points
 * d'entrée : seulement qui peut les atteindre.
 *
 * ⚠️ Le garde d'authentification met en cache le compte de la première requête
 * d'un test. Chaque cas part donc d'un test distinct, un seul profil à la fois.
 */
class MatriceAccesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'organizer', 'client', 'staff'] as $code) {
            UserType::firstOrCreate(['code' => $code], ['name' => $code, 'label' => ucfirst($code)]);
        }

        foreach ([Role::ADMIN, Role::ORGANIZER, Role::CLIENT] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug]);
        }
    }

    private function profil(string $type): User
    {
        $user = User::create([
            'name' => ucfirst($type), 'email' => $type . '-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);

        $user->user_type_id = UserType::where('name', $type)->value('id');
        $user->is_organizer = $type === 'organizer';
        $user->save();

        if (in_array($type, [Role::ADMIN, Role::ORGANIZER, Role::CLIENT], true)) {
            $user->roles()->attach(Role::where('slug', $type)->value('id'));
        }

        if ($type === 'organizer') {
            $organisateur = Organizer::create([
                'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
                'status' => 'active', 'is_active' => true,
            ]);
            $user->organizers()->attach($organisateur->id);
        }

        return $user->fresh();
    }

    /**
     * Les points d'entrée réservés à l'administration.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pointsAdministration(): array
    {
        return [
            'liste des utilisateurs' => ['GET', '/api/v1/admin/users'],
            'liste des événements' => ['GET', '/api/v1/admin/events'],
            'file d\'approbation' => ['GET', '/api/v1/admin/events/approval/queue'],
            'liste des versements' => ['GET', '/api/v1/admin/payouts'],
            'soldes des organisateurs' => ['GET', '/api/v1/admin/payouts/balances'],
        ];
    }

    /** @dataProvider pointsAdministration */
    public function test_un_visiteur_est_refuse_sur_l_administration(string $verbe, string $url): void
    {
        $this->json($verbe, $url)->assertStatus(401);
    }

    /** @dataProvider pointsAdministration */
    public function test_un_client_est_refuse_sur_l_administration(string $verbe, string $url): void
    {
        Sanctum::actingAs($this->profil('client'));

        $this->json($verbe, $url)->assertStatus(403);
    }

    /** @dataProvider pointsAdministration */
    public function test_un_organisateur_est_refuse_sur_l_administration(string $verbe, string $url): void
    {
        // Un organisateur gère SES événements, pas la plateforme. C'est la
        // confusion la plus tentante quand on ajoute une page d'administration.
        Sanctum::actingAs($this->profil('organizer'));

        $this->json($verbe, $url)->assertStatus(403);
    }

    /** @dataProvider pointsAdministration */
    public function test_un_administrateur_est_admis(string $verbe, string $url): void
    {
        // Le pendant indispensable : une matrice qui refuse tout le monde
        // passerait au vert sans rien garantir.
        Sanctum::actingAs($this->profil('admin'));

        $reponse = $this->json($verbe, $url);

        $this->assertNotContains($reponse->status(), [401, 403],
            "l'administration doit rester accessible : {$url}");
    }

    /**
     * Les points d'entrée de l'espace organisateur.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pointsOrganisateur(): array
    {
        return [
            'tableau de bord' => ['GET', '/api/v1/organizer/dashboard'],
            'ses événements' => ['GET', '/api/v1/organizer/events'],
            'son solde' => ['GET', '/api/v1/organizer/balance'],
            'ses versements' => ['GET', '/api/v1/organizer/payouts'],
        ];
    }

    /** @dataProvider pointsOrganisateur */
    public function test_un_visiteur_est_refuse_sur_l_espace_organisateur(string $verbe, string $url): void
    {
        $this->json($verbe, $url)->assertStatus(401);
    }

    /** @dataProvider pointsOrganisateur */
    public function test_un_client_est_refuse_sur_l_espace_organisateur(string $verbe, string $url): void
    {
        Sanctum::actingAs($this->profil('client'));

        $this->assertContains($this->json($verbe, $url)->status(), [403, 404],
            "un client ne doit pas atteindre {$url}");
    }

    /** @dataProvider pointsOrganisateur */
    public function test_un_organisateur_est_admis_chez_lui(string $verbe, string $url): void
    {
        Sanctum::actingAs($this->profil('organizer'));

        $this->assertNotContains($this->json($verbe, $url)->status(), [401, 403],
            "l'espace organisateur doit rester accessible : {$url}");
    }

    /**
     * La supervision de la plateforme.
     *
     * @return array<string, array{0: string}>
     */
    public static function pointsSupervision(): array
    {
        return [
            'santé' => ['/api/v1/admin/supervision/health'],
            'trafic' => ['/api/v1/admin/supervision/traffic'],
            'flux' => ['/api/v1/admin/supervision/flows'],
        ];
    }

    /** @dataProvider pointsSupervision */
    public function test_la_supervision_est_fermee_au_public(string $url): void
    {
        $this->getJson($url)->assertStatus(401);
    }

    /** @dataProvider pointsSupervision */
    public function test_la_supervision_est_fermee_aux_clients(string $url): void
    {
        Sanctum::actingAs($this->profil('client'));

        $this->getJson($url)->assertStatus(403);
    }

    /** @dataProvider pointsSupervision */
    public function test_la_supervision_est_ouverte_aux_administrateurs(string $url): void
    {
        // Décision explicite : la supervision n'est pas réservée aux
        // super-administrateurs. Tout administrateur doit pouvoir constater
        // qu'une tâche de fond ne tourne plus.
        Sanctum::actingAs($this->profil('admin'));

        $this->getJson($url)->assertOk();
    }

    public function test_le_scan_est_ferme_a_un_simple_client(): void
    {
        // Ouvrir la porte est un acte d'exploitation : il ne suffit pas
        // d'avoir un compte.
        Sanctum::actingAs($this->profil('client'));

        $this->postJson('/api/v1/scans', [
            'qr_code' => 'TKT-QUELCONQUE',
            'scanned_at' => now()->toIso8601String(),
            'device_id' => 'appareil',
        ])->assertStatus(403);
    }

    public function test_le_profil_reste_accessible_a_tout_compte(): void
    {
        // Le pendant : un client doit pouvoir gérer son propre compte.
        Sanctum::actingAs($this->profil('client'));

        $this->getJson('/api/v1/auth/me')->assertOk();
        $this->getJson('/api/v1/profile/two-factor')->assertOk();
        $this->getJson('/api/v1/profile/sessions')->assertOk();
    }
}
