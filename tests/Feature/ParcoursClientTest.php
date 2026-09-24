<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Le parcours d'un client avec compte : inscription, connexion, achat,
 * puis retrouver ses billets.
 *
 * L'enchaînement compte plus que chaque étape : c'est le jeton rendu à la
 * connexion qui doit ouvrir la commande, et c'est la commande payée qui doit
 * réapparaître dans « mes commandes ». Une rupture entre deux maillons ne se
 * voit dans aucun test isolé — le client, lui, la voit tout de suite.
 */
class ParcoursClientTest extends TestCase
{
    use RefreshDatabase;

    private const MOT_DE_PASSE = 'un-mot-de-passe-solide';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ebilling.webhook_allowed_ips' => '127.0.0.1']);
    }

    private function evenementEnVente(int $places = 20, int $prix = 7500): TicketType
    {
        $organisateur = Organizer::create([
            'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $evenement = Event::create([
            'organizer_id' => $organisateur->id,
            'title' => 'Soirée Gabon', 'slug' => 'soiree-' . uniqid(),
            'description' => 'x', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
            'service_fee_bearer' => 'platform',
        ]);

        EventSchedule::create([
            'event_id' => $evenement->id,
            'starts_at' => now()->addDays(5)->setTime(20, 0),
            'ends_at' => now()->addDays(5)->setTime(23, 0),
            'status' => 'active',
        ]);

        return TicketType::create([
            'event_id' => $evenement->id, 'name' => 'Standard', 'price' => $prix,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => $places,
        ]);
    }

    /** @return array{0: string, 1: User} le jeton et le compte */
    private function inscrireEtConnecter(): array
    {
        $email = 'client-' . uniqid() . '@primea.test';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Client Fidèle',
            'email' => $email,
            'phone' => '077' . random_int(100000, 999999),
            'password' => self::MOT_DE_PASSE,
            'password_confirmation' => self::MOT_DE_PASSE,
        ])->assertSuccessful();

        $connexion = $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => self::MOT_DE_PASSE,
        ])->assertOk();

        $jeton = $connexion->json('token');
        $this->assertNotEmpty($jeton, 'la connexion doit rendre un jeton');

        return [$jeton, User::where('email', $email)->firstOrFail()];
    }

    /**
     * Oublier l'utilisateur déjà résolu.
     *
     * ⚠️ Le garde d'authentification met en cache le compte de la PREMIÈRE
     * requête et le réutilise pour les suivantes, dans un même test. Sans ce
     * vidage, changer de jeton ne change pas d'utilisateur — et un test de
     * cloisonnement passerait en vérifiant deux fois la même personne, ce qui
     * est pire qu'inutile.
     */
    private function changerDeSession(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function payer(Order $commande): void
    {
        $paiement = Payment::create([
            'order_id' => $commande->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $commande->total_amount, 'status' => 'initiated',
        ]);

        $this->postJson('/api/v1/webhooks/ebilling', [
            'reference' => $paiement->provider_txn_ref,
            'billingid' => '5576' . random_int(100000, 999999),
            'amount' => (int) $commande->total_amount,
            'state' => 'processed',
        ])->assertOk();
    }

    public function test_de_l_inscription_a_mes_billets(): void
    {
        Notification::fake();
        $type = $this->evenementEnVente();

        // ── 1. Inscription puis connexion ──────────────────────────────────
        [$jeton, $client] = $this->inscrireEtConnecter();

        // ── 2. Le profil répond avec ce jeton ──────────────────────────────
        $this->withToken($jeton)->getJson('/api/v1/auth/me')->assertOk();

        // ── 3. Commander ───────────────────────────────────────────────────
        $this->withToken($jeton)->postJson('/api/v1/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 2,
        ])->assertStatus(201);

        $commande = Order::latest('id')->first();

        $this->assertSame($client->id, $commande->buyer_id,
            'la commande doit être rattachée au compte, pas à un invité');
        $this->assertSame('pending', $commande->status);

        // ── 4. Payer ───────────────────────────────────────────────────────
        $this->payer($commande);

        $commande->refresh();
        $this->assertSame('paid', $commande->status);
        $this->assertSame(2, $commande->tickets()->where('status', 'issued')->count());

        // ── 5. La commande apparaît dans son espace ────────────────────────
        $mesCommandes = $this->withToken($jeton)->getJson('/api/v1/orders')->assertOk();

        // La liste expose la référence sous « order_number ».
        $references = collect($mesCommandes->json('orders'))->pluck('order_number');

        $this->assertContains($commande->reference, $references,
            'le client doit retrouver sa commande payée');

        // ── 6. Et son billet se télécharge ─────────────────────────────────
        $billet = $commande->tickets()->first();
        $this->get('/api/v1/tickets/' . $billet->code . '/pdf')->assertOk();
    }

    public function test_sans_jeton_on_ne_commande_pas(): void
    {
        $type = $this->evenementEnVente();

        $this->postJson('/api/v1/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
        ])->assertStatus(401);
    }

    public function test_un_client_ne_voit_pas_les_commandes_d_un_autre(): void
    {
        // Cloisonnement : « mes commandes » doit vouloir dire les miennes.
        Notification::fake();
        $type = $this->evenementEnVente();

        [$jetonA] = $this->inscrireEtConnecter();
        $this->withToken($jetonA)->postJson('/api/v1/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
        ])->assertStatus(201);

        $commandeDeA = Order::latest('id')->first();

        $this->changerDeSession();
        [$jetonB] = $this->inscrireEtConnecter();
        $this->changerDeSession();

        $reponse = $this->withToken($jetonB)->getJson('/api/v1/orders')->assertOk();

        $references = collect($reponse->json('orders'))->pluck('order_number');

        $this->assertNotContains($commandeDeA->reference, $references);
    }

    public function test_un_client_ne_peut_pas_payer_la_commande_d_un_autre(): void
    {
        // Le contrôle qui compte vraiment : régler la commande d'autrui
        // reviendrait à s'en attribuer les billets.
        $type = $this->evenementEnVente();

        [$jetonA] = $this->inscrireEtConnecter();
        $this->withToken($jetonA)->postJson('/api/v1/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
        ])->assertStatus(201);

        $commandeDeA = Order::latest('id')->first();

        $this->changerDeSession();
        [$jetonB] = $this->inscrireEtConnecter();

        $this->changerDeSession();

        $this->withToken($jetonB)->postJson('/api/v1/orders/' . $commandeDeA->id . '/pay', [
            'gateway' => 'airtelmoney',
            'phone' => '077112233',
        ])->assertStatus(404);
    }

    public function test_un_mot_de_passe_trop_court_est_refuse_a_l_inscription(): void
    {
        $reponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Trop Court',
            'email' => 'court-' . uniqid() . '@primea.test',
            'phone' => '077' . random_int(100000, 999999),
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertStatus(422);

        $this->assertArrayHasKey('password', $reponse->json('errors'));
    }

    public function test_un_numero_fantaisiste_est_refuse_a_l_inscription(): void
    {
        // Sans contrôle, ce numéro partait jusqu'à la passerelle au premier
        // achat, et le client voyait un échec de paiement pour une faute de
        // saisie faite des semaines plus tôt.
        $reponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Numéro Faux',
            'email' => 'faux-' . uniqid() . '@primea.test',
            'phone' => 'pas-un-numero',
            'password' => self::MOT_DE_PASSE,
            'password_confirmation' => self::MOT_DE_PASSE,
        ])->assertStatus(422);

        $this->assertArrayHasKey('phone', $reponse->json('errors'));
    }
}
