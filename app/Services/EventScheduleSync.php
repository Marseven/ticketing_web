<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Carbon;

/**
 * Met les séances d'un événement en accord avec ce que l'écran a envoyé.
 *
 * La mise à jour d'un événement supprimait toutes ses séances puis les
 * recréait. Les billets déjà vendus gardaient alors un `schedule_id` pointant
 * vers une ligne disparue : le billet devenait introuvable à la récupération,
 * et la page annonçait un événement passé alors qu'il avait lieu le lendemain.
 * Un simple changement de titre suffisait à casser tous les billets vendus.
 *
 * On réconcilie donc au lieu de tout jeter : les lignes existantes sont
 * modifiées sur place, dans l'ordre chronologique. Un report de date met à
 * jour la ligne sans changer son identifiant — c'est précisément ce qu'il faut
 * pour qu'un billet déjà vendu reste valable après un report.
 */
class EventScheduleSync
{
    /**
     * @param  array<int, array{starts_at?:string, ends_at?:string, door_time?:string}>  $wanted
     */
    public function sync(Event $event, array $wanted): void
    {
        $wanted = $this->sorted($wanted);

        // Les séances existantes, dans le même ordre : la i-ème existante
        // reçoit la i-ème demandée. C'est ce qui garde les identifiants
        // stables, y compris quand les dates changent.
        $existing = $event->schedules()->orderBy('starts_at')->get()->values();

        foreach ($wanted as $index => $schedule) {
            $attributes = [
                'starts_at' => $schedule['starts_at'] ?? null,
                'ends_at' => $schedule['ends_at'] ?? null,
                'status' => 'active',
            ];

            if (array_key_exists('door_time', $schedule)) {
                $attributes['door_time'] = $schedule['door_time'] ?: null;
            }

            if (isset($existing[$index])) {
                $existing[$index]->update($attributes);

                continue;
            }

            $event->schedules()->create($attributes);
        }

        // Séances réellement retirées par l'organisateur : celles qui restent
        // au-delà de ce qui a été envoyé.
        $removed = $existing->slice(count($wanted));

        if ($removed->isNotEmpty()) {
            $event->schedules()->whereIn('id', $removed->pluck('id'))->delete();
        }
    }

    /**
     * Trie par date de début, pour que la réconciliation associe les séances
     * qui se correspondent plutôt que celles qui se suivent dans le formulaire.
     *
     * @param  array<int, array<string, mixed>>  $schedules
     * @return array<int, array<string, mixed>>
     */
    private function sorted(array $schedules): array
    {
        $schedules = array_values(array_filter(
            $schedules,
            fn ($schedule) => filled($schedule['starts_at'] ?? null)
        ));

        usort($schedules, function ($a, $b) {
            return $this->timestamp($a['starts_at']) <=> $this->timestamp($b['starts_at']);
        });

        return $schedules;
    }

    private function timestamp(?string $value): int
    {
        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable $e) {
            // Une date illisible ne doit pas faire tomber l'enregistrement :
            // elle part en fin de liste et la validation la refusera ailleurs.
            return PHP_INT_MAX;
        }
    }
}
