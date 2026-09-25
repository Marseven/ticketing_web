<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Export de la liste des billets vendus d'un événement.
 *
 * Deux usages : le tableur pour rapprocher les ventes et retrouver un
 * acheteur, le PDF pour l'imprimer et l'emporter à l'entrée quand le réseau
 * manque ou que le scanner lâche.
 *
 * Le point sensible n'est pas le format mais l'ACCÈS : c'est un fichier
 * nominatif — noms, adresses, numéros — et le laisser à portée de tout compte
 * reviendrait à publier le fichier clients d'autrui.
 */
class ExportBilletsEvenementTest extends TestCase
{
    use RefreshDatabase;

    private Event $evenement;
    private Organizer $organisateur;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'organizer', 'client'] as $code) {
            UserType::firstOrCreate(['code' => $code], ['name' => $code, 'label' => ucfirst($code)]);
        }
        foreach ([Role::ADMIN, Role::ORGANIZER, Role::CLIENT] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug]);
        }

        $this->organisateur = Organizer::create([
            'name' => 'Chill Prod', 'slug' => 'chill-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->evenement = Event::create([
            'organizer_id' => $this->organisateur->id,
            'title' => 'Chill Expo', 'slug' => 'chill-expo-' . uniqid(),
            'description' => 'x', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
        ]);

        EventSchedule::create([
            'event_id' => $this->evenement->id,
            'starts_at' => now()->addDays(3)->setTime(20, 0),
            'ends_at' => now()->addDays(3)->setTime(23, 0),
            'status' => 'active',
        ]);
    }

    private function utilisateur(string $type, ?Organizer $organisateur = null): User
    {
        $user = User::create([
            'name' => ucfirst($type), 'email' => $type . '-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = UserType::where('name', $type)->value('id');
        $user->is_organizer = $type === 'organizer';
        $user->save();
        $user->roles()->attach(Role::where('slug', $type)->value('id'));

        if ($organisateur) {
            $user->organizers()->attach($organisateur->id);
        }

        return $user->fresh();
    }

    /** Un billet vendu, avec sa commande, son tarif et son numéro payeur. */
    private function billetVendu(string $statut = 'issued', float $prix = 5000): Ticket
    {
        $type = TicketType::firstOrCreate(
            ['event_id' => $this->evenement->id, 'name' => 'Standard'],
            ['price' => $prix, 'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 100]
        );

        $commande = Order::create([
            'organizer_id' => $this->organisateur->id, 'buyer_id' => null,
            'currency' => 'XAF', 'subtotal_amount' => $prix, 'fees_amount' => 0,
            'commission_percentage' => 10, 'tax_amount' => 0, 'total_amount' => $prix,
            'status' => 'paid', 'reference' => 'ORD-' . strtoupper(uniqid()),
            'placed_at' => now(), 'is_guest_order' => true,
            'guest_name' => 'Aimée Nzé', 'guest_email' => 'aimee@example.ga',
            'guest_phone' => '077112233',
        ]);

        OrderItem::create([
            'order_id' => $commande->id, 'event_id' => $this->evenement->id,
            'ticket_type_id' => $type->id, 'unit_price' => $prix, 'qty' => 1,
            'line_total' => $prix,
        ]);

        Payment::create([
            'order_id' => $commande->id, 'provider' => 'moov',
            'provider_txn_ref' => 'PAY-' . strtoupper(uniqid()),
            'amount' => $prix, 'status' => 'success', 'payer_phone' => '066998877',
        ]);

        return Ticket::create([
            'order_id' => $commande->id, 'event_id' => $this->evenement->id,
            'ticket_type_id' => $type->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => $statut,
            'used_at' => $statut === 'used' ? now() : null,
        ]);
    }

    private function url(string $format = 'xlsx'): string
    {
        return "/api/v1/events/{$this->evenement->id}/tickets/export?format={$format}";
    }

    // ── Accès ──────────────────────────────────────────────────────────────

    public function test_un_visiteur_n_exporte_rien(): void
    {
        $this->billetVendu();

        $this->getJson($this->url())->assertStatus(401);
    }

    public function test_un_client_n_exporte_rien(): void
    {
        // Le fichier porte des noms, des e-mails et des numéros : il ne doit
        // pas suffire d'avoir un compte pour l'obtenir.
        $this->billetVendu();
        Sanctum::actingAs($this->utilisateur('client'));

        $this->getJson($this->url())->assertStatus(403);
    }

    public function test_un_organisateur_n_exporte_pas_l_evenement_d_un_autre(): void
    {
        $this->billetVendu();

        $autre = Organizer::create([
            'name' => 'Autre Prod', 'slug' => 'autre-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        Sanctum::actingAs($this->utilisateur('organizer', $autre));

        $this->getJson($this->url())->assertStatus(403);
    }

    public function test_l_organisateur_exporte_son_evenement(): void
    {
        Excel::fake();
        $this->billetVendu();

        Sanctum::actingAs($this->utilisateur('organizer', $this->organisateur));

        $this->get($this->url())->assertOk();
    }

    public function test_l_administration_exporte_n_importe_quel_evenement(): void
    {
        Excel::fake();
        $this->billetVendu();

        Sanctum::actingAs($this->utilisateur('admin'));

        $this->get($this->url())->assertOk();
    }

    // ── Contenu ────────────────────────────────────────────────────────────

    public function test_le_tableur_porte_un_nom_reconnaissable(): void
    {
        Excel::fake();
        $this->billetVendu();
        Sanctum::actingAs($this->utilisateur('admin'));

        $this->get($this->url())->assertOk();

        Excel::assertDownloaded('billets-chill-expo-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function test_les_billets_annules_ne_sont_pas_comptes_comme_vendus(): void
    {
        // Un billet annulé ne correspond ni à une place occupée ni à un
        // encaissement : le faire figurer fausserait la recette.
        $this->billetVendu('issued');
        $this->billetVendu('void');
        $this->billetVendu('used');

        $lignes = (new \App\Exports\EventTicketsExport($this->evenement))->collection();

        $this->assertCount(2, $lignes);
        $this->assertNotContains('void', $lignes->pluck('status'));
    }

    public function test_chaque_ligne_porte_les_deux_numeros_et_le_prix_paye(): void
    {
        // Le numéro du compte et celui qui a PAYÉ diffèrent souvent — on
        // achète pour un proche — et c'est par le second qu'un acheteur
        // retrouve son billet quand il appelle.
        $billet = $this->billetVendu(prix: 7500);

        $export = new \App\Exports\EventTicketsExport($this->evenement);
        $ligne = $export->map($export->collection()->first());

        $this->assertSame($billet->code, $ligne[0]);
        $this->assertSame('Aimée Nzé', $ligne[3]);
        $this->assertSame('077112233', $ligne[5], 'téléphone du compte');
        $this->assertSame('066998877', $ligne[6], 'téléphone de paiement');
        $this->assertSame(7500.0, (float) $ligne[8], 'le prix PAYÉ, pas le tarif du jour');
    }

    public function test_un_billet_scanne_est_marque_comme_entre(): void
    {
        $this->billetVendu('used');

        $export = new \App\Exports\EventTicketsExport($this->evenement);
        $ligne = $export->map($export->collection()->first());

        $this->assertSame('Entré', $ligne[11]);
        $this->assertNotSame('—', $ligne[13], 'l\'heure d\'entrée est renseignée');
    }

    public function test_le_pdf_se_telecharge_et_porte_le_titre_de_l_evenement(): void
    {
        $this->billetVendu();
        Sanctum::actingAs($this->utilisateur('admin'));

        $reponse = $this->get($this->url('pdf'))->assertOk();

        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));
        $this->assertStringContainsString('billets-chill-expo', $reponse->headers->get('content-disposition'));
        // La signature du format : on vérifie qu'un vrai PDF sort, pas une
        // page d'erreur servie avec le bon en-tête.
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_un_evenement_sans_vente_produit_un_document_vide_et_non_une_erreur(): void
    {
        // Exporter avant la première vente est un geste banal ; il ne doit pas
        // se solder par une erreur.
        Sanctum::actingAs($this->utilisateur('admin'));

        $this->get($this->url('pdf'))->assertOk();
    }

    public function test_un_format_inconnu_est_refuse(): void
    {
        Sanctum::actingAs($this->utilisateur('admin'));

        $this->getJson($this->url('docx'))->assertStatus(422);
    }
}
