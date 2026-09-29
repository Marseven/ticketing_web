<?php

namespace App\Services;

use App\Models\Event;
use App\Models\TicketPrice;
use App\Models\TicketType;
use Illuminate\Support\Collection;

/**
 * Met les paliers de prix d'un événement en accord avec le formulaire.
 *
 * Un palier dit : « ce billet vaut tant, entre telle date et telle date ».
 * Hors de toute période, c'est le prix de la catégorie qui s'applique — le
 * plein tarif. C'est le mécanisme de la prévente : 5 500 F jusqu'au
 * 1er décembre, 10 000 F ensuite.
 *
 * ⚠️ Les paliers ne valent QUE si `events.use_variable_pricing` est vrai.
 * Enregistrer des paliers sans lever ce drapeau les rend inertes : tout le
 * monde paie le plein tarif, sans que rien ne le signale. Le drapeau est donc
 * posé ici, à partir de ce que le formulaire a envoyé, et jamais séparément.
 *
 * Contrairement aux séances et aux catégories, un palier ne porte aucune
 * donnée vendue : rien ne pointe dessus. On peut donc le remplacer sans
 * précaution particulière — ce que fait ce service, en repartant de ce que
 * l'écran a envoyé.
 */
class TicketPriceSync
{
    /**
     * @param  ?array<int, array{ticket_type_id?:mixed, ticket_index?:mixed, price?:mixed,
     *                           valid_from?:?string, valid_until?:?string,
     *                           priority?:mixed, description?:?string}>  $tiers
     *                           `null` = non transmis, donc à ne pas toucher.
     */
    public function sync(Event $event, bool $enabled, ?array $tiers): void
    {
        $event->forceFill(['use_variable_pricing' => $enabled])->save();

        $categories = $event->ticketTypes()->orderBy('id')->get();

        // Désactivé : on efface. Laisser traîner d'anciens paliers derrière un
        // drapeau baissé prépare une surprise le jour où quelqu'un le relève.
        if (! $enabled) {
            $this->wipe($categories);

            return;
        }

        // ⚠️ `null` signifie « l'écran n'a pas parlé des paliers », et non
        // « supprime-les ». Sans cette distinction, un formulaire qui ignore
        // cette fonctionnalité — ou qui ne les a pas chargés — effaçait la
        // grille tarifaire d'un événement au premier enregistrement, en
        // ramenant tout le monde au plein tarif sans que rien ne le signale.
        if ($tiers === null) {
            return;
        }

        $this->wipe($categories);

        foreach ($tiers as $rang => $tier) {
            $categorie = $this->categorie($tier, $categories);

            if (! $categorie || ! is_numeric($tier['price'] ?? null)) {
                continue;
            }

            TicketPrice::create([
                'ticket_type_id' => $categorie->id,
                'currency' => $categorie->currency ?? 'XAF',
                'price' => $tier['price'],
                'valid_from' => blank($tier['valid_from'] ?? null) ? null : $tier['valid_from'],
                'valid_until' => blank($tier['valid_until'] ?? null) ? null : $tier['valid_until'],
                // Départage deux périodes qui se recouvrent : le plus grand
                // l'emporte. À défaut, l'ordre de saisie fait foi.
                'priority' => (int) ($tier['priority'] ?? $rang + 1),
                'description' => $tier['description'] ?? null,
                'status' => 'active',
            ]);
        }
    }

    /**
     * La catégorie visée par ce palier.
     *
     * Par identifiant quand l'écran le connaît — modification d'un événement
     * existant — sinon par position dans la liste des catégories envoyées,
     * car à la création elles n'ont pas encore d'identifiant.
     *
     * @param  array<string, mixed>  $tier
     * @param  Collection<int, TicketType>  $categories
     */
    private function categorie(array $tier, Collection $categories): ?TicketType
    {
        if (! empty($tier['ticket_type_id'])) {
            return $categories->firstWhere('id', (int) $tier['ticket_type_id']);
        }

        if (isset($tier['ticket_index']) && is_numeric($tier['ticket_index'])) {
            return $categories->values()->get((int) $tier['ticket_index']);
        }

        // Une seule catégorie : aucune ambiguïté, inutile d'exiger un renvoi.
        return $categories->count() === 1 ? $categories->first() : null;
    }

    /** @param  Collection<int, TicketType>  $categories */
    private function wipe(Collection $categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        TicketPrice::whereIn('ticket_type_id', $categories->pluck('id'))->delete();
    }
}
