<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le parcours d'un organisateur : de la création de l'événement à la vente.
 *
 * L'étape décisive est l'approbation. Un événement créé n'est PAS en vente :
 * il attend la validation d'un administrateur, qui fixe au passage la
 * commission. Ce point a déjà causé une panne — un événement créé par
 * l'administration restait invisible au public, parce que son
 * `approval_status` valait « pending » sans que personne ne le sache.
 *
 * On suit donc : création → invisible → approbation avec commission →
 * visible → vente → la commission retenue est bien celle qui a été fixée.
 */
class ParcoursOrganisateurTest extends TestCase
{
    use RefreshDatabase;

    private Organizer $organisateur;
    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ebilling.webhook_allowed_ips' => '127.0.0.1']);

        foreach (['admin', 'organizer', 'client'] as $code) {
            UserType::firstOrCreate(['code' => $code], ['name' => $code, 'label' => ucfirst($code)]);
        }

        foreach ([Role::ADMIN, Role::ORGANIZER, Role::CLIENT] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug]);
        }

        $this->organisateur = Organizer::create([
            'name' => 'Chill Prod', 'slug' => 'chill-prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->patron = $this->utilisateur('organizer');
        $this->patron->organizers()->attach($this->organisateur->id);
    }

    private function utilisateur(string $type): User
    {
        $user = User::create([
            'name' => ucfirst($type), 'email' => $type . '-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);

        $user->user_type_id = UserType::where('name', $type)->value('id');
        // Les contrôleurs se fondent sur cet indicateur, et non sur le seul
        // rôle : un vrai compte organisateur le porte.
        $user->is_organizer = $type === 'organizer';
        $user->save();
        $user->roles()->attach(Role::where('slug', $type)->value('id'));

        return $user->fresh();
    }

    private function creerEvenement(): Event
    {
        $categorie = EventCategory::create([
            'name' => 'Concert', 'slug' => 'concert-' . uniqid(), 'is_active' => true,
        ]);

        Sanctum::actingAs($this->patron);

        $this->postJson('/api/v1/organizer/events', [
            'title' => 'Nuit Blanche',
            'description' => 'Une longue nuit',
            'category_id' => $categorie->id,
            // L'organisateur met en ligne. Cela ne suffit pas : l'événement
            // reste en attente d'approbation, et c'est tout l'objet de ces
            // tests.
            'is_active' => true,
            'schedules' => [[
                'starts_at' => now()->addDays(10)->setTime(21, 0)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(11)->setTime(4, 0)->format('Y-m-d H:i:s'),
            ]],
            'ticket_types' => [[
                'name' => 'Standard', 'price' => 10000, 'capacity' => 100,
            ]],
        ])->assertSuccessful();

        return Event::where('title', 'Nuit Blanche')->latest('id')->firstOrFail();
    }

    private function estVisiblePubliquement(Event $evenement): bool
    {
        $liste = $this->getJson('/api/client/events')->assertOk();

        return collect($liste->json('events'))->contains('slug', $evenement->slug);
    }

    public function test_un_evenement_cree_n_est_pas_encore_en_vente(): void
    {
        // Le piège : l'organisateur croit son événement en ligne, le public ne
        // le voit pas, et rien ne l'explique.
        $evenement = $this->creerEvenement();

        $this->assertSame('pending', $evenement->approval_status);
        $this->assertFalse($this->estVisiblePubliquement($evenement),
            'un événement non approuvé ne doit pas être listé');
    }

    public function test_l_approbation_le_met_en_vente_et_fixe_la_commission(): void
    {
        $evenement = $this->creerEvenement();

        Sanctum::actingAs($this->utilisateur('admin'));

        $this->postJson('/api/v1/admin/events/' . $evenement->id . '/approve', [
            'commission_percentage' => 15,
        ])->assertSuccessful();

        $evenement->refresh();

        $this->assertSame('approved', $evenement->approval_status);
        $this->assertSame('15.00', (string) $evenement->commission_percentage);
        $this->assertTrue($this->estVisiblePubliquement($evenement),
            'une fois approuvé, le public doit le voir');
    }

    public function test_un_evenement_rejete_reste_invisible(): void
    {
        $evenement = $this->creerEvenement();

        Sanctum::actingAs($this->utilisateur('admin'));

        $this->postJson('/api/v1/admin/events/' . $evenement->id . '/reject', [
            'rejection_reason' => 'Dossier incomplet',
        ])->assertSuccessful();

        $this->assertSame('rejected', $evenement->fresh()->approval_status);
        $this->assertFalse($this->estVisiblePubliquement($evenement->fresh()));
    }

    public function test_la_commission_fixee_est_celle_qui_est_retenue_a_la_vente(): void
    {
        // Elle était codée en dur à 10 %. Un taux négocié par événement ne
        // sert à rien s'il n'est pas appliqué à l'encaissement.
        Notification::fake();
        $evenement = $this->creerEvenement();

        Sanctum::actingAs($this->utilisateur('admin'));
        $this->postJson('/api/v1/admin/events/' . $evenement->id . '/approve', [
            'commission_percentage' => 20,
        ])->assertSuccessful();

        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $evenement->fresh()->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => 'Acheteur',
            'guest_email' => 'acheteur@example.ga',
            'guest_phone' => '077112233',
        ])->assertStatus(201);

        $commande = Order::latest('id')->first();

        $this->assertSame('20.00', (string) $commande->commission_percentage,
            'le taux négocié doit être figé sur la commande, pas les 10 % d\'origine');
    }

    public function test_un_organisateur_ne_voit_pas_les_evenements_d_un_autre(): void
    {
        // Cloisonnement : chacun chez soi. C'est la garantie la plus simple à
        // casser lors d'un ajout de filtre.
        $this->creerEvenement();

        $autreOrganisateur = Organizer::create([
            'name' => 'Autre Prod', 'slug' => 'autre-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $intrus = $this->utilisateur('organizer');
        $intrus->organizers()->attach($autreOrganisateur->id);

        Sanctum::actingAs($intrus);

        $reponse = $this->getJson('/api/v1/organizer/events')->assertOk();

        $titres = collect($reponse->json('data.events') ?? $reponse->json('events') ?? $reponse->json('data'))
            ->pluck('title');

        $this->assertNotContains('Nuit Blanche', $titres,
            'les événements d\'un autre organisateur ne doivent pas apparaître');
    }

    public function test_un_client_ne_peut_pas_creer_d_evenement(): void
    {
        Sanctum::actingAs($this->utilisateur('client'));

        $this->postJson('/api/v1/organizer/events', ['title' => 'Tentative'])
            ->assertStatus(403);
    }

    public function test_un_organisateur_ne_peut_pas_approuver_son_propre_evenement(): void
    {
        // Sinon l'approbation ne vaut rien : chacun se validerait, commission
        // comprise.
        $evenement = $this->creerEvenement();

        Sanctum::actingAs($this->patron);

        $this->postJson('/api/v1/admin/events/' . $evenement->id . '/approve', [
            'commission_percentage' => 0,
        ])->assertStatus(403);

        $this->assertSame('pending', $evenement->fresh()->approval_status);
    }
}
