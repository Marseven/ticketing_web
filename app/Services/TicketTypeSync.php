<?php

namespace App\Services;

use App\Models\Event;
use App\Models\TicketType;

/**
 * Met les catégories de billets d'un événement en accord avec le formulaire.
 *
 * La mise à jour d'un événement supprimait toutes ses catégories puis les
 * recréait. Or `tickets.ticket_type_id` pointe dessus : les billets déjà
 * vendus se retrouvaient rattachés à une ligne effacée. La catégorie et le
 * tarif disparaissaient du billet, les décomptes de places repartaient de
 * zéro, et la page de récupération tombait carrément en erreur — « Attempt to
 * read property "name" on null » s'est affiché à un acheteur.
 *
 * On réconcilie donc au lieu de tout jeter, et surtout : **une catégorie qui a
 * déjà vendu des billets n'est jamais supprimée**. Elle est désactivée, ce qui
 * la retire de la vente sans rompre le lien avec les billets émis. Effacer une
 * ligne dont dépendent des billets payés n'est pas une mise à jour, c'est une
 * perte de données.
 */
class TicketTypeSync
{
    /**
     * @param  array<int, array{id?:int, name?:string, price?:mixed, capacity?:mixed, description?:string}>  $wanted
     */
    public function sync(Event $event, array $wanted): void
    {
        $existing = $event->ticketTypes()->get()->keyBy('id');
        $kept = [];

        foreach ($wanted as $payload) {
            if (blank($payload['name'] ?? null)) {
                continue;
            }

            $type = $this->match($payload, $existing, $kept);

            $attributes = [
                'name' => $payload['name'],
                'price' => $payload['price'] ?? 0,
                'available_quantity' => $payload['capacity'] ?? null,
                'max_quantity' => $payload['capacity'] ?? null,
                'description' => $payload['description'] ?? null,
                'status' => 'active',
            ];

            if ($type) {
                $type->update($attributes);
                $kept[] = $type->id;

                continue;
            }

            $kept[] = $event->ticketTypes()->create($attributes)->id;
        }

        $this->retire($event, $existing, $kept);
    }

    /**
     * Retrouve la catégorie visée : par identifiant si le formulaire l'envoie,
     * sinon par nom exact. Le nom est ce que voit l'organisateur, et il ne le
     * change presque jamais en même temps qu'autre chose.
     *
     * @param  array<string, mixed>  $payload
     * @param  \Illuminate\Support\Collection<int, TicketType>  $existing
     * @param  array<int>  $kept
     */
    private function match(array $payload, $existing, array $kept): ?TicketType
    {
        if (! empty($payload['id']) && $existing->has((int) $payload['id'])) {
            return $existing->get((int) $payload['id']);
        }

        return $existing->first(fn (TicketType $type) => ! in_array($type->id, $kept, true)
            && mb_strtolower(trim($type->name)) === mb_strtolower(trim((string) $payload['name'])));
    }

    /**
     * Retire les catégories absentes du formulaire.
     *
     * ⚠️ Celles qui ont déjà vendu sont DÉSACTIVÉES, pas supprimées : un
     * billet payé doit garder sa catégorie et son tarif, y compris après que
     * l'organisateur a retiré la catégorie de sa grille.
     *
     * @param  \Illuminate\Support\Collection<int, TicketType>  $existing
     * @param  array<int>  $kept
     */
    private function retire(Event $event, $existing, array $kept): void
    {
        foreach ($existing as $type) {
            if (in_array($type->id, $kept, true)) {
                continue;
            }

            if ($type->tickets()->exists() || $type->orderItems()->exists()) {
                $type->update(['status' => 'inactive']);

                continue;
            }

            $type->delete();
        }
    }
}
