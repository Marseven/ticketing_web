<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Order;
use App\Models\Organizer;
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
 * Les exports d'analytique de l'administration.
 *
 * Signalés en erreur 500 en production sur `/admin/analytics/export/events`.
 * Ces trois exports n'avaient aucun test : ils étaient donc libres de tomber
 * en panne sans que rien ne le signale.
 */
class ExportAnalytiqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserType::firstOrCreate(['code' => 'admin'], ['name' => 'admin', 'label' => 'Admin']);
        Role::firstOrCreate(['slug' => Role::ADMIN], ['name' => Role::ADMIN, 'description' => 'admin']);

        $admin = User::create([
            'name' => 'Patron', 'email' => 'patron-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $admin->user_type_id = UserType::where('name', 'admin')->value('id');
        $admin->save();
        $admin->roles()->attach(Role::where('slug', Role::ADMIN)->value('id'));

        Sanctum::actingAs($admin->fresh());
    }

    /** Un événement vendu, tel qu'il existe réellement en base. */
    private function evenementAvecVentes(): Event
    {
        $organisateur = Organizer::create([
            'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $evenement = Event::create([
            'organizer_id' => $organisateur->id,
            'title' => 'Chill Expo', 'slug' => 'chill-' . uniqid(),
            'description' => 'x', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
        ]);

        EventSchedule::create([
            'event_id' => $evenement->id,
            'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(3),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $evenement->id, 'name' => 'Standard', 'price' => 5000,
            'currency' => 'XAF', 'status' => 'active', 'available_quantity' => 50,
        ]);

        $commande = Order::create([
            'organizer_id' => $organisateur->id, 'currency' => 'XAF',
            'subtotal_amount' => 5000, 'fees_amount' => 0, 'commission_percentage' => 10,
            'tax_amount' => 0, 'total_amount' => 5000, 'status' => 'paid',
            'reference' => 'ORD-' . strtoupper(uniqid()), 'placed_at' => now(),
            'is_guest_order' => true, 'guest_name' => 'Client',
        ]);

        Ticket::create([
            'order_id' => $commande->id, 'event_id' => $evenement->id,
            'ticket_type_id' => $type->id, 'code' => 'TKT-' . strtoupper(uniqid()),
            'status' => 'issued',
        ]);

        return $evenement;
    }

    private function url(string $quoi): string
    {
        return "/api/v1/admin/analytics/export/{$quoi}?"
            . http_build_query([
                'start_date' => now()->subMonth()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]);
    }

    public function test_l_export_des_evenements_aboutit(): void
    {
        // PAS de simulacre ici : c'est la génération réelle du tableur qui
        // tombait en production, et un simulacre l'aurait masquée.
        $this->evenementAvecVentes();

        // La réponse est un fichier binaire : on vérifie qu'il sort bien, et
        // qu'aucune exception n'a été levée en le fabriquant.
        $this->get($this->url('events'))->assertSuccessful();
    }

    public function test_l_export_des_ventes_aboutit(): void
    {
        Excel::fake();
        $this->evenementAvecVentes();

        $this->get($this->url('sales'))->assertOk();
    }

    public function test_l_export_financier_aboutit(): void
    {
        Excel::fake();
        $this->evenementAvecVentes();

        $this->get($this->url('financial'))->assertOk();
    }

    public function test_un_export_sans_donnees_ne_tombe_pas_en_erreur(): void
    {
        // Exporter une période vide est un geste banal : il ne doit pas se
        // solder par une erreur 500.
        Excel::fake();

        $this->get($this->url('events'))->assertOk();
    }

    public function test_les_chiffres_de_l_export_des_evenements_sont_justes(): void
    {
        // Les comptages et la recette sont passés des boucles PHP à la base.
        // Un calcul déplacé est un calcul qui peut changer de résultat sans
        // que personne ne s'en aperçoive : ce test fige les valeurs.
        $evenement = $this->evenementAvecVentes();

        $export = new \App\Exports\EventsExport(
            now()->subMonth()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $ligne = $export->map($export->collection()->firstWhere('id', $evenement->id));

        $this->assertSame('Chill Expo', $ligne[0]);
        $this->assertSame(1, $ligne[8], 'un billet vendu');
        $this->assertSame(0, $ligne[9], 'aucun billet utilisé');
        $this->assertSame(50, $ligne[10], 'la capacité déclarée');
        $this->assertSame(2.0, $ligne[11], 'taux de remplissage : 1 sur 50');
        $this->assertSame('5 000', $ligne[12], 'la recette de la commande payée');
    }

    public function test_l_export_ne_lance_pas_une_requete_par_evenement(): void
    {
        // La cause du 500 en production : l'export chargeait tous les billets
        // de tous les événements, puis relançait trois requêtes par ligne.
        for ($i = 0; $i < 5; $i++) {
            $this->evenementAvecVentes();
        }

        $export = new \App\Exports\EventsExport(
            now()->subMonth()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );
        $evenements = $export->collection();

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $evenements->each(fn ($evenement) => $export->map($evenement));
        $requetes = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(0, $requetes,
            'la mise en forme ne doit déclencher aucune requête');
    }
}
