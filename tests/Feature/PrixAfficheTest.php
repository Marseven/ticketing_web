<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Organizer;
use App\Models\TicketPrice;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le prix annoncé est le même partout.
 *
 * La liste publique calculait le prix EFFECTIF d'un événement en prévente ;
 * la fiche de détail renvoyait le prix catalogue. Un événement à 5 500 F en
 * prévente s'affichait donc à 5 500 F sur l'accueil et à 10 000 F sur sa
 * propre page — celle où l'on décide d'acheter, et celle qu'on partage.
 *
 * Constaté sur RANDONNE, dont l'affiche annonçait « PASS 5 500 FCFA » pendant
 * que la fiche réclamait le plein tarif.
 */
class PrixAfficheTest extends TestCase
{
    use RefreshDatabase;

    private function evenementEnPrevente(): Event
    {
        $organisateur = Organizer::create([
            'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $evenement = Event::create([
            'organizer_id' => $organisateur->id,
            'title' => 'Randonnée', 'slug' => 'rando-' . uniqid(),
            'description' => 'x', 'status' => 'published',
            'approval_status' => 'approved', 'is_active' => true,
            'use_variable_pricing' => true,
        ]);

        EventSchedule::create([
            'event_id' => $evenement->id,
            'starts_at' => now()->addMonths(3), 'ends_at' => now()->addMonths(3)->addHours(5),
            'status' => 'active',
        ]);

        $type = TicketType::create([
            'event_id' => $evenement->id, 'name' => 'Standard',
            // Le prix catalogue est le PLEIN TARIF : c'est lui qu'on ne doit
            // pas montrer pendant la prévente.
            'price' => 10000, 'currency' => 'XAF',
            'status' => 'active', 'available_quantity' => 1500,
        ]);

        TicketPrice::create([
            'ticket_type_id' => $type->id, 'currency' => 'XAF', 'price' => 5500,
            'valid_from' => null, 'valid_until' => now()->addMonth(),
            'priority' => 1, 'description' => 'Prévente', 'status' => 'active',
        ]);

        TicketPrice::create([
            'ticket_type_id' => $type->id, 'currency' => 'XAF', 'price' => 10000,
            'valid_from' => now()->addMonth()->addSecond(), 'valid_until' => null,
            'priority' => 2, 'description' => 'Plein tarif', 'status' => 'active',
        ]);

        return $evenement;
    }

    private function prixSurLaFiche(Event $evenement): float
    {
        $fiche = $this->getJson('/api/client/events/' . $evenement->slug)->assertOk();

        return (float) data_get($fiche->json(), 'event.ticket_types.0.price',
            data_get($fiche->json(), 'data.ticket_types.0.price',
                data_get($fiche->json(), 'ticket_types.0.price')));
    }

    private function prixDansLaListe(Event $evenement): float
    {
        $liste = $this->getJson('/api/client/events')->assertOk();

        $vu = collect($liste->json('events'))->firstWhere('slug', $evenement->slug);

        return (float) data_get($vu, 'ticket_types.0.price');
    }

    public function test_la_fiche_annonce_le_prix_de_prevente(): void
    {
        $evenement = $this->evenementEnPrevente();

        $this->assertSame(5500.0, $this->prixSurLaFiche($evenement),
            'la fiche doit annoncer le tarif de prévente, pas le plein tarif');
    }

    public function test_la_fiche_et_la_liste_disent_la_meme_chose(): void
    {
        // C'est l'invariant qui compte : deux écrans qui annoncent deux prix
        // différents pour le même billet font mentir l'affiche de
        // l'organisateur.
        $evenement = $this->evenementEnPrevente();

        $this->assertSame(
            $this->prixDansLaListe($evenement),
            $this->prixSurLaFiche($evenement)
        );
    }

    public function test_la_fiche_annonce_le_prochain_palier(): void
    {
        // De quoi dire « 5 500 F jusqu'au 1er décembre » plutôt que de laisser
        // l'acheteur découvrir la hausse.
        $evenement = $this->evenementEnPrevente();

        $fiche = $this->getJson('/api/client/events/' . $evenement->slug)->assertOk();

        $suivant = data_get($fiche->json(), 'event.ticket_types.0.next_tier',
            data_get($fiche->json(), 'data.ticket_types.0.next_tier',
                data_get($fiche->json(), 'ticket_types.0.next_tier')));

        $this->assertNotNull($suivant, 'le palier suivant doit être publié');
        $this->assertSame(10000.0, (float) $suivant['price']);
    }

    public function test_un_evenement_sans_tarification_variable_garde_son_prix(): void
    {
        // Le cas ordinaire ne doit pas changer.
        $evenement = $this->evenementEnPrevente();
        $evenement->update(['use_variable_pricing' => false]);

        $this->assertSame(10000.0, $this->prixSurLaFiche($evenement));
    }

    public function test_apres_la_bascule_le_plein_tarif_s_applique(): void
    {
        $evenement = $this->evenementEnPrevente();

        $this->travelTo(now()->addMonths(2));

        $this->assertSame(10000.0, $this->prixSurLaFiche($evenement));
    }
}
