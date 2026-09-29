<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketPrice;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Gestion des paliers de prix depuis l'administration.
 *
 * Le moteur existait — `ticket_prices`, `use_variable_pricing`,
 * `getPriceFor()` — et l'espace organisateur savait poser des paliers À LA
 * CRÉATION. L'administration, elle, n'y avait aucun accès : ni à la création,
 * ni à la modification. Il fallait écrire un seeder pour mettre un événement
 * en prévente, ce qui a été fait pour RANDONNE.
 *
 * Le piège récurrent : des paliers enregistrés sans lever
 * `use_variable_pricing` sont INERTES. Tout le monde paie le plein tarif et
 * rien ne le signale — l'affiche annonce 5 500 F, la caisse encaisse 10 000 F.
 */
class PaliersDePrixAdminTest extends TestCase
{
    use RefreshDatabase;

    private Organizer $organisateur;
    private EventCategory $categorie;

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

        $this->organisateur = Organizer::create([
            'name' => 'Prod', 'slug' => 'prod-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->categorie = EventCategory::create([
            'name' => 'Concert', 'slug' => 'concert-' . uniqid(),
        ]);
    }

    /** @return array<string, mixed> */
    private function formulaire(array $surcharges = []): array
    {
        return array_merge([
            'title' => 'Randonnée',
            'description' => 'Une marche',
            'status' => 'published',
            'organizer_id' => $this->organisateur->id,
            'category_id' => $this->categorie->id,
            'schedules' => [[
                'starts_at' => now()->addMonths(3)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addMonths(3)->addHours(5)->format('Y-m-d H:i:s'),
            ]],
            'ticket_types' => [[
                'name' => 'Standard', 'price' => 10000, 'capacity' => 1500,
            ]],
        ], $surcharges);
    }

    private function paliers(): array
    {
        return [
            [
                'ticket_index' => 0, 'price' => 5500,
                'valid_from' => null,
                'valid_until' => now()->addMonths(2)->format('Y-m-d H:i:s'),
                'description' => 'Prévente',
            ],
            [
                'ticket_index' => 0, 'price' => 10000,
                'valid_from' => now()->addMonths(2)->addSecond()->format('Y-m-d H:i:s'),
                'valid_until' => null,
                'description' => 'Plein tarif',
            ],
        ];
    }

    private function evenementCree(): Event
    {
        return Event::where('title', 'Randonnée')->latest('id')->firstOrFail();
    }

    // ── Création ───────────────────────────────────────────────────────────

    public function test_l_administration_cree_un_evenement_en_prevente(): void
    {
        $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => $this->paliers(),
        ]))->assertSuccessful();

        $evenement = $this->evenementCree();

        $this->assertTrue((bool) $evenement->use_variable_pricing,
            'le drapeau doit être levé, sans quoi les paliers sont inertes');

        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->assertSame(2, TicketPrice::where('ticket_type_id', $type->id)->count());
        $this->assertSame(5500.0, (float) $type->getPriceFor(null, null, now()->addMonth()->toDateTimeString()));
        $this->assertSame(10000.0, (float) $type->getPriceFor(null, null, now()->addMonths(3)->toDateTimeString()));
    }

    public function test_sans_paliers_l_evenement_garde_son_prix_unique(): void
    {
        $this->postJson('/api/v1/admin/events', $this->formulaire())->assertSuccessful();

        $evenement = $this->evenementCree();
        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->assertFalse((bool) $evenement->use_variable_pricing);
        $this->assertSame(0, TicketPrice::where('ticket_type_id', $type->id)->count());
        $this->assertSame(10000.0, (float) $type->getPriceFor());
    }

    // ── Modification ───────────────────────────────────────────────────────

    public function test_l_administration_modifie_les_paliers_apres_coup(): void
    {
        // C'est ce qui manquait le plus : une fois l'événement créé, plus
        // personne ne pouvait toucher aux tarifs.
        $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => $this->paliers(),
        ]))->assertSuccessful();

        $evenement = $this->evenementCree();
        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->putJson('/api/v1/admin/events/' . $evenement->id, [
            'use_variable_pricing' => true,
            'ticket_types' => [[
                'id' => $type->id, 'name' => 'Standard', 'price' => 10000, 'capacity' => 1500,
            ]],
            'price_tiers' => [[
                'ticket_type_id' => $type->id, 'price' => 4000,
                'valid_until' => now()->addMonths(2)->format('Y-m-d H:i:s'),
                'description' => 'Prévente revue à la baisse',
            ]],
        ])->assertSuccessful();

        $this->assertSame(1, TicketPrice::where('ticket_type_id', $type->id)->count(),
            'les anciens paliers sont remplacés, pas empilés');
        $this->assertSame(4000.0, (float) $type->fresh()->getPriceFor(null, null, now()->addMonth()->toDateTimeString()));
    }

    public function test_desactiver_la_prevente_efface_les_paliers(): void
    {
        // Laisser traîner des paliers derrière un drapeau baissé prépare une
        // surprise le jour où quelqu'un le relève.
        $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => $this->paliers(),
        ]))->assertSuccessful();

        $evenement = $this->evenementCree();
        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        $this->putJson('/api/v1/admin/events/' . $evenement->id, [
            'use_variable_pricing' => false,
            'ticket_types' => [[
                'id' => $type->id, 'name' => 'Standard', 'price' => 10000, 'capacity' => 1500,
            ]],
        ])->assertSuccessful();

        $this->assertFalse((bool) $evenement->fresh()->use_variable_pricing);
        $this->assertSame(0, TicketPrice::where('ticket_type_id', $type->id)->count());
        $this->assertSame(10000.0, (float) $type->fresh()->getPriceFor());
    }

    // ── Garde-fous ─────────────────────────────────────────────────────────

    public function test_une_periode_qui_finit_avant_de_commencer_est_refusee(): void
    {
        $reponse = $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => [[
                'ticket_index' => 0, 'price' => 5500,
                'valid_from' => now()->addMonths(3)->format('Y-m-d H:i:s'),
                'valid_until' => now()->addMonth()->format('Y-m-d H:i:s'),
            ]],
        ]))->assertStatus(422);

        $this->assertArrayHasKey('price_tiers.0.valid_until', $reponse->json('errors'));
    }

    public function test_le_prix_affiche_au_public_suit_le_palier(): void
    {
        // Le bout de la chaîne : ce que voit l'acheteur sur la fiche.
        $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => $this->paliers(),
        ]))->assertSuccessful();

        $evenement = $this->evenementCree();

        $fiche = $this->getJson('/api/client/events/' . $evenement->slug)->assertOk();

        $prix = data_get($fiche->json(), 'event.ticket_types.0.price',
            data_get($fiche->json(), 'data.ticket_types.0.price',
                data_get($fiche->json(), 'ticket_types.0.price')));

        $this->assertSame(5500.0, (float) $prix);
    }

    public function test_un_formulaire_muet_sur_les_paliers_ne_les_efface_pas(): void
    {
        // Le formulaire d'administration ne connaissait pas les paliers : sans
        // cette garantie, le premier enregistrement d'un événement en prévente
        // effaçait sa grille tarifaire et ramenait tout le monde au plein
        // tarif, en silence.
        $this->postJson('/api/v1/admin/events', $this->formulaire([
            'use_variable_pricing' => true,
            'price_tiers' => $this->paliers(),
        ]))->assertSuccessful();

        $evenement = $this->evenementCree();
        $type = TicketType::where('event_id', $evenement->id)->firstOrFail();

        // Un enregistrement qui ne dit RIEN des paliers.
        $this->putJson('/api/v1/admin/events/' . $evenement->id, [
            'title' => 'Randonnée renommée',
            'use_variable_pricing' => true,
            'ticket_types' => [[
                'id' => $type->id, 'name' => 'Standard', 'price' => 10000, 'capacity' => 1500,
            ]],
        ])->assertSuccessful();

        $this->assertSame(2, TicketPrice::where('ticket_type_id', $type->id)->count(),
            'les paliers survivent à un enregistrement qui les ignore');
        $this->assertSame(5500.0,
            (float) $type->fresh()->getPriceFor(null, null, now()->addMonth()->toDateTimeString()));
    }
}
