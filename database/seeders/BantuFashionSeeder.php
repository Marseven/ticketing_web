<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Seeder;

/**
 * Seeder de l'organisateur « Bantu Fashion ».
 *
 * Exécution à la demande :
 *   php artisan db:seed --class=BantuFashionSeeder
 *
 * Crée l'organisateur, puis le rattache comme CO-organisateur (affichage
 * « Organisée par … et … », pas de partage d'argent) des événements « Chill
 * Expo » — co-organisés avec OVRF. Idempotent.
 */
class BantuFashionSeeder extends Seeder
{
    public function run(): void
    {
        $bantu = Organizer::firstOrCreate(
            ['slug' => 'bantu-fashion'],
            [
                'name' => 'Bantu Fashion',
                'status' => 'active',
                'is_active' => true,
                'default_commission_percentage' => 10,
            ]
        );

        // Rattacher Bantu Fashion en co-organisateur des events « Chill Expo »
        // (organisés par OVRF). On n'écrase pas un co-organisateur déjà défini
        // vers un autre organisateur.
        Event::where('title', 'like', '%Chill Expo%')
            ->where('organizer_id', '!=', $bantu->id)
            ->where(function ($q) use ($bantu) {
                $q->whereNull('co_organizer_id')->orWhere('co_organizer_id', $bantu->id);
            })
            ->update(['co_organizer_id' => $bantu->id]);
    }
}
