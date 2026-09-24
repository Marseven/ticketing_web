<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\OrganizerBalance;
use App\Models\Payout;
use App\Models\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Le parcours d'un versement à l'organisateur : demande, envoi à SHAP,
 * rappel, solde.
 *
 * C'est le chemin par lequel l'argent SORT. Les garanties qui comptent ne
 * portent pas sur une étape mais sur leur enchaînement : le solde doit être
 * débité une fois et une seule, un rappel rejoué ne doit rien recréditer, et
 * personne ne doit pouvoir demander un versement sur le solde d'autrui.
 */
class ParcoursVersementTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'secret-de-rappel';
    private const NUMERO = '077112233';

    private Organizer $organisateur;
    private User $patron;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.shap.webhook_secret' => self::SECRET]);

        // La passerelle de versement est simulée : sans cela l'appel réel part
        // sur le réseau, échoue au bout de son délai, et le test mesure une
        // panne au lieu du parcours.
        Http::fake([
            '*auth*' => Http::response([
                'access_token' => 'jeton-shap', 'expires_in' => 3600,
            ], 200),
            // Le service vérifie d'abord le solde du compte SHAP : sans cette
            // réponse, le versement est refusé et le montant rendu — ce qui
            // ressemble à s'y méprendre à « le solde n'est jamais débité ».
            // Le compte SHAP finance les versements avec son solde PAYIN de
            // l'opérateur visé : la réponse doit porter cette forme exacte,
            // sinon le versement est refusé et le montant rendu — ce qui
            // ressemble à s'y méprendre à « le solde n'est jamais débité ».
            '*balance*' => Http::response(['data' => [
                ['payment_system_name' => 'airtelmoney', 'category' => 'PAYIN', 'amount' => 10000000],
            ]], 200),
            '*payout*' => Http::response([
                'successful' => 'transaction_initiated',
                'response' => ['payout_id' => 'SHAP-1'],
            ], 200),
        ]);

        foreach (['organizer', 'client'] as $code) {
            UserType::firstOrCreate(['code' => $code], ['name' => $code, 'label' => ucfirst($code)]);
        }
        foreach ([Role::ORGANIZER, Role::CLIENT] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => $slug]);
        }

        $this->organisateur = Organizer::create([
            'name' => 'Chill Prod', 'slug' => 'chill-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->patron = $this->organisateurAvecSolde($this->organisateur, 200000);
    }

    private function organisateurAvecSolde(Organizer $organisateur, float $solde): User
    {
        OrganizerBalance::create([
            'organizer_id' => $organisateur->id, 'gateway' => 'airtelmoney',
            'balance' => $solde, 'pending_balance' => 0,
        ]);

        $user = User::create([
            'name' => 'Patron', 'email' => 'patron-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $user->user_type_id = UserType::where('name', 'organizer')->value('id');
        $user->is_organizer = true;
        $user->save();
        $user->roles()->attach(Role::where('slug', Role::ORGANIZER)->value('id'));
        $user->organizers()->attach($organisateur->id);

        return $user->fresh();
    }

    private function solde(Organizer $organisateur): float
    {
        return (float) OrganizerBalance::where('organizer_id', $organisateur->id)
            ->where('gateway', 'airtelmoney')->value('balance');
    }

    private function demander(float $montant)
    {
        return $this->postJson('/api/v1/organizer/payouts', [
            'gateway' => 'airtelmoney',
            'amount' => $montant,
            'phone_number' => self::NUMERO,
        ]);
    }

    private function rappel(Payout $versement, string $statut)
    {
        return $this->postJson('/api/v1/webhooks/shap-payout', [
            'external_reference' => $versement->external_reference,
            'status' => $statut,
        ], ['X-Webhook-Secret' => self::SECRET]);
    }

    public function test_un_versement_demande_debite_le_solde_une_fois(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->patron);

        $rep = $this->demander(50000);
        fwrite(STDERR, "\nDEMANDE(" . $rep->status() . ") " . substr($rep->getContent(), 0, 260)
            . "\n  payouts=" . Payout::count() . " solde=" . $this->solde($this->organisateur) . "\n");
        $rep->assertSuccessful();

        $versement = Payout::latest('id')->firstOrFail();

        $this->assertSame(50000.0, (float) $versement->amount);
        $this->assertSame($this->organisateur->id, $versement->organizer_id);
        $this->assertSame(150000.0, $this->solde($this->organisateur),
            'le solde est débité au moment de la demande');
    }

    public function test_on_ne_demande_pas_plus_que_son_solde(): void
    {
        Sanctum::actingAs($this->patron);

        $this->demander(500000)->assertStatus(400);

        $this->assertSame(200000.0, $this->solde($this->organisateur), 'le solde n\'a pas bougé');
        $this->assertSame(0, Payout::count());
    }

    public function test_un_echec_rend_le_montant_une_seule_fois(): void
    {
        // Les passerelles rejouent leurs notifications. Sans garde, chaque
        // rappel « échec » recréditait le montant : de l'argent créé sans
        // qu'aucune attaque soit nécessaire.
        Notification::fake();
        Sanctum::actingAs($this->patron);

        $this->demander(50000)->assertSuccessful();
        $versement = Payout::latest('id')->firstOrFail();

        $this->rappel($versement, 'failed')->assertOk();
        $this->rappel($versement, 'failed')->assertOk();
        $this->rappel($versement, 'failed')->assertOk();

        $this->assertSame('failed', $versement->fresh()->status);
        $this->assertSame(200000.0, $this->solde($this->organisateur),
            'le montant est rendu une fois, quel que soit le nombre de rappels');
    }

    public function test_un_versement_reussi_ne_rend_rien(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->patron);

        $this->demander(50000)->assertSuccessful();
        $versement = Payout::latest('id')->firstOrFail();

        $this->rappel($versement, 'success')->assertOk();

        $this->assertSame('success', $versement->fresh()->status);
        $this->assertSame(150000.0, $this->solde($this->organisateur),
            'l\'argent est parti : il ne revient pas au solde');
    }

    public function test_un_rappel_non_authentifie_ne_touche_a_rien(): void
    {
        // Ce point d'entrée était public : connaître une référence suffisait à
        // gonfler un solde en répétant l'appel.
        Notification::fake();
        Sanctum::actingAs($this->patron);

        $this->demander(50000)->assertSuccessful();
        $versement = Payout::latest('id')->firstOrFail();

        $this->postJson('/api/v1/webhooks/shap-payout', [
            'external_reference' => $versement->external_reference,
            'status' => 'failed',
        ])->assertStatus(403);

        $this->assertSame(150000.0, $this->solde($this->organisateur));
        $this->assertNotSame('failed', $versement->fresh()->status);
    }

    public function test_on_ne_demande_pas_de_versement_sur_le_solde_d_un_autre(): void
    {
        // Cloisonnement : le solde suit l'organisateur, pas le demandeur.
        $autre = Organizer::create([
            'name' => 'Autre Prod', 'slug' => 'autre-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $voisin = $this->organisateurAvecSolde($autre, 0);

        Sanctum::actingAs($voisin);

        $this->demander(50000)->assertStatus(400);

        $this->assertSame(200000.0, $this->solde($this->organisateur),
            'le solde du premier organisateur est intact');
    }

    public function test_un_client_ne_demande_aucun_versement(): void
    {
        $client = User::create([
            'name' => 'Client', 'email' => 'client-' . uniqid() . '@primea.test',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        $client->user_type_id = UserType::where('name', 'client')->value('id');
        $client->save();

        Sanctum::actingAs($client);

        $this->demander(1000)->assertStatus(403);
    }

    public function test_un_montant_derisoire_ou_un_numero_invalide_sont_refuses(): void
    {
        Sanctum::actingAs($this->patron);

        $this->postJson('/api/v1/organizer/payouts', [
            'gateway' => 'airtelmoney', 'amount' => 10, 'phone_number' => self::NUMERO,
        ])->assertStatus(422);

        $this->postJson('/api/v1/organizer/payouts', [
            'gateway' => 'airtelmoney', 'amount' => 50000, 'phone_number' => 'abc',
        ])->assertStatus(422);

        $this->assertSame(200000.0, $this->solde($this->organisateur));
    }
}
