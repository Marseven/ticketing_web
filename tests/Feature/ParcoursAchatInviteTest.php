<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le parcours d'achat complet, sans compte : de la page d'accueil au contrôle
 * à l'entrée.
 *
 * Chaque étape a déjà ses tests. Ce qui manquait, c'est de les ENCHAÎNER. Les
 * pannes de la soirée l'ont montré : les séances orphelines, la catégorie
 * supprimée, le prix perdu — chaque brique passait isolément, et pourtant le
 * client se retrouvait devant un billet introuvable ou un événement annoncé
 * passé alors qu'il avait lieu le lendemain. Un parcours ne se vérifie qu'en
 * entier.
 *
 * On suit donc un même billet du premier clic jusqu'au portique :
 * liste publique → fiche → commande → paiement → émission → récupération par
 * téléphone → PDF → scan → refus du second scan.
 */
class ParcoursAchatInviteTest extends TestCase
{
    use RefreshDatabase;

    private const ACHETEUR = 'Aimée Nzé';
    private const TELEPHONE = '077112233';

    protected function setUp(): void
    {
        parent::setUp();

        // Le contrôle d'origine du rappel a ses propres tests ; ici on ouvre
        // par l'adresse d'où partent les requêtes de test.
        config(['services.ebilling.webhook_allowed_ips' => '127.0.0.1']);
    }

    private function evenementEnVente(int $places = 50, int $prix = 5000): TicketType
    {
        $organisateur = Organizer::create([
            'name' => 'Chill Prod', 'slug' => 'chill-prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $evenement = Event::create([
            'organizer_id' => $organisateur->id,
            'title' => 'Chill Expo', 'slug' => 'chill-expo-' . uniqid(),
            'description' => 'Une soirée', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
            'service_fee_bearer' => 'platform',
        ]);

        EventSchedule::create([
            'event_id' => $evenement->id,
            'starts_at' => now()->addDays(3)->setTime(20, 0),
            'ends_at' => now()->addDays(3)->setTime(23, 59),
            'status' => 'active',
        ]);

        return TicketType::create([
            'event_id' => $evenement->id, 'name' => 'Standard', 'price' => $prix,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => $places,
        ]);
    }

    /** Régler la commande comme le ferait la notification d'e-billing. */
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

    /** Un agent habilité à scanner pour cet organisateur. */
    private function agent(Event $evenement): User
    {
        UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);
        Role::firstOrCreate(['slug' => Role::ADMIN], ['name' => Role::ADMIN, 'description' => 'admin']);

        $agent = User::create([
            'name' => 'Portier', 'email' => 'portier-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $agent->user_type_id = UserType::where('name', 'admin')->value('id');
        $agent->save();
        $agent->roles()->attach(Role::where('slug', Role::ADMIN)->value('id'));

        return $agent;
    }

    /** Scanner un billet comme le fait l'application de contrôle. */
    private function scanner(string $code)
    {
        return $this->postJson('/api/v1/scans', [
            'qr_code' => $code,
            'scanned_at' => now()->toIso8601String(),
            'device_id' => 'portique-1',
        ]);
    }

    public function test_de_la_page_d_accueil_au_portique(): void
    {
        Notification::fake();
        $type = $this->evenementEnVente();
        $evenement = $type->event;

        // ── 1. L'événement est visible publiquement ────────────────────────
        $liste = $this->getJson('/api/client/events')->assertOk();
        $vu = collect($liste->json('events'))->firstWhere('slug', $evenement->slug);

        $this->assertNotNull($vu, 'l\'événement publié et approuvé doit être listé');
        $this->assertFalse((bool) ($vu['is_sold_out'] ?? true), 'des places restent à vendre');

        // ── 2. Sa fiche donne le prix et la date ───────────────────────────
        $fiche = $this->getJson('/api/client/events/' . $evenement->slug)->assertOk();
        $this->assertSame($evenement->title, data_get($fiche->json(), 'event.title')
            ?? data_get($fiche->json(), 'data.title')
            ?? data_get($fiche->json(), 'title'));

        // ── 3. La commande retient les places sans créer de billet ─────────
        $reponse = $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $evenement->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 2,
            'guest_name' => self::ACHETEUR,
            'guest_email' => 'aimee@example.ga',
            'guest_phone' => self::TELEPHONE,
        ])->assertStatus(201);

        $commande = Order::latest('id')->first();
        $this->assertSame('pending', $commande->status);
        $this->assertSame(0, $commande->tickets()->count(), 'pas de billet avant paiement');
        $this->assertSame(48, $type->fresh()->remaining_quantity, 'deux places retenues');

        // ── 4. Le paiement émet les billets ────────────────────────────────
        $this->payer($commande);

        $commande->refresh();
        $this->assertSame('paid', $commande->status);
        $this->assertSame(2, $commande->tickets()->where('status', 'issued')->count());
        $this->assertSame(48, $type->fresh()->remaining_quantity, 'le décompte ne bouge pas deux fois');

        // ── 5. L'acheteur retrouve ses billets avec son numéro ─────────────
        $recherche = $this->getJson('/api/v1/guest/tickets/search?' . http_build_query([
            'name' => self::ACHETEUR,
            'phone' => self::TELEPHONE,
        ]))->assertOk();

        $codes = collect($recherche->json('data.tickets'))->pluck('code');
        $billet = $commande->tickets()->first();

        $this->assertContains($billet->code, $codes, 'le billet payé doit être retrouvable');

        // Ce que la page affiche doit être complet : c'est là qu'un « Attempt
        // to read property "name" on null » s'est montré à un acheteur.
        $ligne = collect($recherche->json('data.tickets'))->firstWhere('code', $billet->code);
        $this->assertNotEmpty(data_get($ligne, 'event.title'));
        $this->assertNotNull(data_get($ligne, 'ticket_type.name'), 'la catégorie doit survivre');
        $this->assertNotNull(data_get($ligne, 'ticket_type.price'), 'le prix payé doit être connu');
        $this->assertSame(5000.0, (float) data_get($ligne, 'ticket_type.price'),
            'le prix PAYÉ, pas le tarif du jour');

        // ── 6. Le PDF se télécharge ────────────────────────────────────────
        $this->get('/api/v1/tickets/' . $billet->code . '/pdf')->assertOk();

        // ── 7. Le soir venu, le portique accepte le billet, une fois ──────
        //
        // On achète trois jours avant et on entre le soir même : scanner un
        // billet avant l'ouverture est refusé, et c'est voulu.
        $this->travelTo(now()->addDays(3)->setTime(21, 0));

        Sanctum::actingAs($this->agent($evenement));

        $scan = $this->scanner($billet->code)->assertOk();
        $this->assertSame('valid', $scan->json('result'), 'premier scan accepté');
        $this->assertSame('used', $billet->fresh()->status);

        // ── 8. Et refuse le second ─────────────────────────────────────────
        $rescan = $this->scanner($billet->code);
        $this->assertSame('duplicate', $rescan->json('result'), 'un billet ne sert qu\'une fois');
        $this->assertFalse((bool) $rescan->json('success'));
    }

    public function test_le_second_billet_de_la_meme_commande_reste_valable(): void
    {
        // Scanner un billet ne doit pas consommer ceux de ses voisins : deux
        // personnes entrent avec la même commande.
        Notification::fake();
        $type = $this->evenementEnVente();

        $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 2,
            'guest_name' => self::ACHETEUR,
            'guest_email' => 'aimee@example.ga',
            'guest_phone' => self::TELEPHONE,
        ])->assertStatus(201);

        $commande = Order::latest('id')->first();
        $this->payer($commande);

        $this->travelTo(now()->addDays(3)->setTime(21, 0));

        Sanctum::actingAs($this->agent($type->event));

        [$premier, $second] = $commande->fresh()->tickets->all();

        $this->scanner($premier->code)->assertOk();

        $this->assertSame('used', $premier->fresh()->status);
        $this->assertSame('issued', $second->fresh()->status, 'le second billet reste valable');

        $this->assertSame('valid', $this->scanner($second->code)->json('result'));
    }

    public function test_un_billet_non_paye_n_ouvre_pas_la_porte(): void
    {
        // La commande retient la place, mais elle ne donne aucun droit d'entrée
        // tant qu'elle n'est pas réglée.
        Notification::fake();
        $type = $this->evenementEnVente();

        $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => self::ACHETEUR,
            'guest_email' => 'aimee@example.ga',
            'guest_phone' => self::TELEPHONE,
        ])->assertStatus(201);

        $commande = Order::latest('id')->first();
        $this->assertSame(0, $commande->tickets()->count());

        Sanctum::actingAs($this->agent($type->event));

        $refus = $this->scanner('TKT-INVENTE');

        $this->assertNotSame('valid', $refus->json('result'), 'la porte reste fermée');
        $this->assertFalse((bool) $refus->json('success'));
    }

    public function test_la_derniere_place_ne_se_vend_pas_deux_fois(): void
    {
        // Le cas qui se découvre au contrôle d'accès : deux acheteurs, une
        // seule place.
        Notification::fake();
        $type = $this->evenementEnVente(places: 1);

        $commande = fn () => $this->postJson('/api/v1/guest/orders', [
            'event_slug' => $type->event->slug,
            'ticket_type_id' => $type->id,
            'quantity' => 1,
            'guest_name' => self::ACHETEUR,
            'guest_email' => 'aimee@example.ga',
            'guest_phone' => self::TELEPHONE,
        ]);

        $commande()->assertStatus(201);
        $refus = $commande()->assertStatus(400);

        $this->assertSame('SOLD_OUT', $refus->json('error_code'));

        // Et la fiche publique dit la même chose que la caisse.
        $liste = $this->getJson('/api/client/events')->assertOk();
        $vu = collect($liste->json('events'))->firstWhere('slug', $type->event->slug);

        $this->assertTrue((bool) $vu['is_sold_out'], 'l\'affichage suit le refus de la caisse');
    }
}
