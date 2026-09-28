<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventSchedule;
use App\Models\Organizer;
use App\Models\OrganizerBalance;
use App\Models\Role;
use App\Models\TicketPrice;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserType;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Mise en ligne de l'événement « RANDONNE » (Village PANGA, Tchibanga).
 *
 * Tout passe par `firstOrCreate` sur une clé stable : le seeder peut être
 * rejoué sans créer de doublon ni écraser ce qu'un organisateur aurait modifié
 * entre-temps depuis son espace.
 *
 * Deux points méritent d'être connus de qui relira ce fichier.
 *
 * 1. Un événement n'est visible du public que s'il réunit TROIS conditions :
 *    `status = published`, `is_active = true` ET `approval_status = approved`.
 *    Oublier la troisième donne un événement que l'organisateur croit en ligne
 *    et que personne ne voit — c'est déjà arrivé en production.
 *
 * 2. La prévente ne fonctionne que si `use_variable_pricing` est vrai. Sans ce
 *    drapeau, les paliers sont ignorés et c'est le prix du type de billet qui
 *    s'applique — ici 10 000 F au lieu de 5 500 F, donc le tarif de prévente
 *    perdu pour tous les acheteurs.
 */
class RandonneEventSeeder extends Seeder
{
    private const SLUG = 'randonne';
    private const EMAIL = 'deejayketchoos@gmail.com';
    private const TELEPHONE = '065980907';

    public function run(): void
    {
        DB::transaction(function () {
            // L'organisateur d'abord : le lieu lui appartient, la colonne est
            // obligatoire.
            [$organisateur, $responsable] = $this->organisateur();
            $categorie = $this->categorie();
            $lieu = $this->lieu($organisateur);
            $evenement = $this->evenement($organisateur, $categorie, $lieu);

            $this->seance($evenement);
            $this->billetterie($evenement);

            $this->command?->info('Événement RANDONNE prêt — id ' . $evenement->id
                . ', organisateur ' . $organisateur->id
                . ', responsable ' . $responsable->id);
            $this->command?->line('  Page publique : /events/' . $evenement->slug);
        });
    }

    private function categorie(): EventCategory
    {
        $categorie = EventCategory::firstOrCreate(
            ['slug' => 'concert'],
            ['name' => 'Concert']
        );

        $this->command?->line('  Catégorie : ' . $categorie->name . ' (#' . $categorie->id . ')');

        return $categorie;
    }

    /**
     * Le lieu n'a pas de slug : on l'identifie par son nom et sa ville, seule
     * combinaison stable dont on dispose.
     */
    private function lieu(Organizer $organisateur): Venue
    {
        $lieu = Venue::firstOrCreate(
            ['name' => 'Village PANGA', 'city' => 'Tchibanga'],
            [
                // Obligatoire en base : un lieu appartient à l'organisateur qui
                // l'a saisi.
                'organizer_id' => $organisateur->id,
                'address' => 'PANGA, Tchibanga',
                'country' => 'Gabon',
                'status' => 'active',
            ]
        );

        $this->command?->line('  Lieu : ' . $lieu->name . ' (#' . $lieu->id . ')');

        return $lieu;
    }

    /**
     * @return array{0: Organizer, 1: User}
     */
    private function organisateur(): array
    {
        $organisateur = Organizer::firstOrCreate(
            ['slug' => 'abessolo-mba-rodrigue'],
            [
                'name' => 'Abessolo MBA Rodrigue',
                'contact_email' => self::EMAIL,
                'contact_phone' => self::TELEPHONE,
                'status' => 'active',
                'is_active' => true,
            ]
        );

        $responsable = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Abessolo MBA Rodrigue',
                'phone' => self::TELEPHONE,
                'status' => 'active',
                // Mot de passe aléatoire : le compte s'ouvre par « mot de passe
                // oublié ». Un mot de passe connu du seeder serait un mot de
                // passe connu de quiconque lit le dépôt.
                'password' => Hash::make(Str::random(32)),
            ]
        );

        $responsable->forceFill([
            'is_organizer' => true,
            'user_type_id' => UserType::where('name', 'organizer')->value('id')
                ?? $responsable->user_type_id,
        ])->save();

        if ($role = Role::where('slug', Role::ORGANIZER)->value('id')) {
            $responsable->roles()->syncWithoutDetaching([$role]);
        }

        $responsable->organizers()->syncWithoutDetaching([$organisateur->id]);

        // Un solde par opérateur : c'est lui qui reçoit la recette en mode
        // différé, et il doit exister avant la première vente.
        OrganizerBalance::firstOrCreate(
            ['organizer_id' => $organisateur->id, 'gateway' => 'airtelmoney'],
            ['balance' => 0, 'pending_balance' => 0, 'phone_number' => self::TELEPHONE]
        );

        $this->command?->line('  Organisateur : ' . $organisateur->name . ' (#' . $organisateur->id . ')');

        return [$organisateur, $responsable];
    }

    private function evenement(Organizer $organisateur, EventCategory $categorie, Venue $lieu): Event
    {
        $evenement = Event::firstOrCreate(
            ['slug' => self::SLUG],
            [
                'organizer_id' => $organisateur->id,
                'category_id' => $categorie->id,
                'venue_id' => $lieu->id,
                'title' => 'RANDONNE',
                'description' => 'Découverte, Jeux, Mini Concert, Divertissement',

                // Les trois conditions de visibilité, ensemble.
                'status' => 'published',
                'is_active' => true,
                'approval_status' => 'approved',
                'approved_at' => now(),

                'published_at' => now(),

                // Ouverture des ventes : la fiche s'affiche avant, avec un
                // compte à rebours, et l'achat est refusé jusque-là.
                'sales_start_at' => '2026-10-28 08:20:00',
                'show_remaining_seats' => false,

                // Indispensable à la prévente : sans ce drapeau, les paliers
                // plus bas ne sont jamais consultés.
                'use_variable_pricing' => true,

                // Le client paie le prix affiché ; les frais de service ne lui
                // sont pas ajoutés. ⚠️ Le champ ne connaît que « customer » et
                // « platform » : il n'existe pas de valeur « organisateur ».
                'service_fee_bearer' => 'platform',

                // Versement à chaque vente, vers le numéro de l'organisateur.
                'payout_mode' => 'instant',
                'instant_payout_phone' => self::TELEPHONE,
            ]
        );

        $this->command?->line('  Événement : ' . $evenement->title . ' (#' . $evenement->id . ')');

        return $evenement;
    }

    private function seance(Event $evenement): void
    {
        $seance = EventSchedule::firstOrCreate(
            ['event_id' => $evenement->id, 'starts_at' => '2026-12-26 06:30:00'],
            [
                'ends_at' => '2026-12-26 11:30:00',
                // ⚠️ Ouverture des portes annoncée APRÈS le départ : c'est ce
                // que disait la fiche. Cohérent pour une randonnée qui part tôt
                // et rejoint le village ensuite, mais à confirmer.
                'door_time' => '2026-12-26 08:30:00',
                'status' => 'active',
            ]
        );

        $this->command?->line('  Séance : ' . $seance->starts_at->format('d/m/Y H:i')
            . ' → ' . $seance->ends_at->format('H:i'));
    }

    private function billetterie(Event $evenement): void
    {
        $type = TicketType::firstOrCreate(
            ['event_id' => $evenement->id, 'name' => 'Standard'],
            [
                // Le prix de repli, appliqué hors de tout palier : c'est le
                // plein tarif, jamais celui de la prévente.
                'price' => 10000,
                'currency' => 'XAF',
                'available_quantity' => 1500,
                'max_quantity' => 1500,
                'status' => 'active',
            ]
        );

        $paliers = [
            ['prix' => 5500, 'du' => null, 'au' => '2026-12-01 23:59:59', 'quoi' => 'Prévente'],
            ['prix' => 10000, 'du' => '2026-12-02 00:00:00', 'au' => null, 'quoi' => 'Plein tarif'],
        ];

        foreach ($paliers as $rang => $palier) {
            TicketPrice::firstOrCreate(
                [
                    'ticket_type_id' => $type->id,
                    'price' => $palier['prix'],
                    'valid_from' => $palier['du'],
                ],
                [
                    'currency' => 'XAF',
                    'valid_until' => $palier['au'],
                    // Le plus grand l'emporte quand deux paliers se recouvrent.
                    'priority' => $rang + 1,
                    'description' => $palier['quoi'],
                    'status' => 'active',
                ]
            );

            $this->command?->line('  Palier : ' . number_format($palier['prix'], 0, ',', ' ')
                . ' F — ' . $palier['quoi']);
        }

        $this->command?->line('  Billets : ' . $type->available_quantity . ' places');
    }
}
