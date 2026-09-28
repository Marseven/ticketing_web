<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\UserType;
use Database\Seeders\RandonneEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mise en ligne de l'événement RANDONNE.
 *
 * Un seeder d'événement réel n'est pas un jeu de démonstration : il met des
 * places en vente. Ce qui compte n'est pas qu'il s'exécute, mais que
 * l'événement soit RÉELLEMENT visible et vendu au bon prix — deux choses qui
 * dépendent de drapeaux faciles à oublier.
 */
class RandonneEventSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['organizer', 'client'] as $code) {
            UserType::firstOrCreate(['code' => $code], ['name' => $code, 'label' => ucfirst($code)]);
        }
        Role::firstOrCreate(['slug' => Role::ORGANIZER], ['name' => Role::ORGANIZER, 'description' => 'organizer']);

        $this->seed(RandonneEventSeeder::class);
    }

    private function evenement(): Event
    {
        return Event::where('slug', 'randonne')->firstOrFail();
    }

    public function test_l_evenement_est_reellement_visible_du_public(): void
    {
        // Trois conditions, pas une : publié, actif ET approuvé. En oublier
        // une donne un événement que l'organisateur croit en ligne et que
        // personne ne voit.
        $evenement = $this->evenement();

        $this->assertSame('published', $evenement->status);
        $this->assertTrue((bool) $evenement->is_active);
        $this->assertSame('approved', $evenement->approval_status);

        $liste = $this->getJson('/api/client/events')->assertOk();

        $this->assertTrue(
            collect($liste->json('events'))->contains('slug', 'randonne'),
            'l\'événement doit apparaître sur la liste publique'
        );
    }

    public function test_la_prevente_est_reellement_active(): void
    {
        // Sans `use_variable_pricing`, les paliers sont ignorés et tout le
        // monde paie le plein tarif : 10 000 F au lieu de 5 500 F.
        $evenement = $this->evenement();

        $this->assertTrue((bool) $evenement->use_variable_pricing);

        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->assertSame(
            5500.0,
            (float) $type->getPriceFor(null, null, '2026-11-15 10:00:00'),
            'avant le 1er décembre, le tarif de prévente s\'applique'
        );

        $this->assertSame(
            10000.0,
            (float) $type->getPriceFor(null, null, '2026-12-10 10:00:00'),
            'après la bascule, le plein tarif s\'applique'
        );
    }

    public function test_les_places_et_la_date_sont_celles_annoncees(): void
    {
        $evenement = $this->evenement();
        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();
        $seance = $evenement->schedules()->firstOrFail();

        $this->assertSame(1500, (int) $type->available_quantity);
        $this->assertSame('26/12/2026 06:30', $seance->starts_at->format('d/m/Y H:i'));
        $this->assertSame('26/12/2026 11:30', $seance->ends_at->format('d/m/Y H:i'));
    }

    public function test_le_versement_immediat_vise_le_bon_numero(): void
    {
        // C'est par ce numéro que l'argent sort à chaque vente : une erreur ici
        // envoie la recette à quelqu'un d'autre.
        $evenement = $this->evenement();

        $this->assertSame('instant', $evenement->payout_mode);
        $this->assertSame('065980907', $evenement->instant_payout_phone);
        $this->assertSame(9, strlen($evenement->instant_payout_phone),
            'la passerelle exige exactement neuf chiffres');
    }

    public function test_le_client_paie_le_prix_affiche(): void
    {
        // Les frais de service ne lui sont pas ajoutés.
        $this->assertFalse($this->evenement()->customerBearsServiceFee());
    }

    public function test_les_ventes_n_ouvrent_pas_avant_la_date_prevue(): void
    {
        $evenement = $this->evenement();

        $this->assertSame(
            '28/10/2026 08:20',
            $evenement->sales_start_at->format('d/m/Y H:i')
        );
    }

    public function test_rejouer_le_seeder_ne_duplique_rien(): void
    {
        // Un seeder d'événement réel sera relancé — après un correctif, une
        // restauration. Il ne doit ni doubler les places ni créer un second
        // événement homonyme.
        $this->seed(RandonneEventSeeder::class);
        $this->seed(RandonneEventSeeder::class);

        $this->assertSame(1, Event::where('slug', 'randonne')->count());
        $this->assertSame(1, TicketType::where('event_id', $this->evenement()->id)->count());
        $this->assertSame(1, $this->evenement()->schedules()->count());
        $this->assertSame(2, \App\Models\TicketPrice::whereIn(
            'ticket_type_id',
            TicketType::where('event_id', $this->evenement()->id)->pluck('id')
        )->count(), 'les deux paliers, pas quatre');
    }
}
