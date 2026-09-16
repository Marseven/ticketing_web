<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organizer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder dédié à l'événement « MADE IN GABAO » (COD'ON EVENT — Session 28).
 *
 * Exécution à la demande :
 *   php artisan db:seed --class=MadeInGabaoEventSeeder
 *
 * Détails :
 *  - Organisateur : Cod'On
 *  - Lieu : Institut Français (Libreville)
 *  - Date : samedi 17 octobre 2026, 09h → 12h
 *  - Frais de service : à la charge du CLIENT (service_fee_bearer = customer)
 *  - Billets : Standard 200 XAF (150 places), VIP 500 XAF (50 places)
 *
 * Idempotent : ré-exécutable sans doublon (updateOrCreate + purge des
 * schedules/ticket_types de cet événement avant recréation).
 */
class MadeInGabaoEventSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Organisateur Cod'On
        $organizer = Organizer::firstOrCreate(
            ['slug' => 'cod-on'],
            [
                'name' => "Cod'On",
                'contact_email' => 'contact@codon.ga',
                'status' => 'active',
                'is_active' => true,
                'default_commission_percentage' => 10,
            ]
        );

        // 2. Catégorie « Conférence » (table event_categories)
        $categoryId = DB::table('event_categories')->where('slug', 'conference')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('event_categories')->insertGetId([
                'name' => 'Conférence',
                'slug' => 'conference',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Lieu : Institut Français (Libreville)
        $venueId = DB::table('venues')
            ->where('name', 'Institut Français')
            ->where('city', 'Libreville')
            ->value('id');
        if (!$venueId) {
            $venueId = DB::table('venues')->insertGetId([
                'organizer_id' => $organizer->id,
                'name' => 'Institut Français',
                'city' => 'Libreville',
                'address' => "Institut Français du Gabon, Libreville",
                'geo_lat' => 0.3924,
                'geo_lng' => 9.4536,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Événement
        $event = Event::updateOrCreate(
            ['slug' => 'made-in-gabao-session-28'],
            [
                'organizer_id' => $organizer->id,
                'category_id' => $categoryId,
                'venue_id' => $venueId,
                'title' => 'MADE IN GABAO',
                'description' => "COD'ON EVENT — Session 28 : session d'échanges et de partage d'expériences.\n\n"
                    . "Le futur du Gabon se code ici, par nous, pour nous. #MadeInGabao\n\n"
                    . "Inscription gratuite — places limitées.",
                'status' => 'published',
                'approval_status' => 'approved',
                'is_active' => true,
                'use_variable_pricing' => false,
                'commission_percentage' => null, // => utilise le défaut organisateur (10%)
                'service_fee_bearer' => 'customer', // le CLIENT supporte les frais de service
                'payout_mode' => 'deferred',
                'published_at' => now(),
            ]
        );

        // 5. Date/horaire : samedi 17 octobre 2026, 09h → 12h
        DB::table('event_schedules')->where('event_id', $event->id)->delete();
        DB::table('event_schedules')->insert([
            'event_id' => $event->id,
            'starts_at' => Carbon::create(2026, 10, 17, 9, 0, 0),
            'ends_at' => Carbon::create(2026, 10, 17, 12, 0, 0),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Types de billets (purge puis recréation)
        DB::table('ticket_types')->where('event_id', $event->id)->delete();
        $tickets = [
            [
                'name' => 'Standard',
                'description' => 'Accès général à la session',
                'price' => 200,
                'available_quantity' => 150,
                'max_quantity' => 10,
            ],
            [
                'name' => 'VIP',
                'description' => 'Accès VIP à la session',
                'price' => 500,
                'available_quantity' => 50,
                'max_quantity' => 5,
            ],
        ];
        foreach ($tickets as $t) {
            DB::table('ticket_types')->insert([
                'event_id' => $event->id,
                'name' => $t['name'],
                'description' => $t['description'],
                'price' => $t['price'],
                'currency' => 'XAF',
                'available_quantity' => $t['available_quantity'],
                'max_quantity' => $t['max_quantity'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("✅ Événement « MADE IN GABAO » créé.");
        $this->command->info("   Organisateur : {$organizer->name} (#{$organizer->id})");
        $this->command->info("   Lieu : Institut Français, Libreville (#{$venueId})");
        $this->command->info("   Date : 17/10/2026 09h-12h");
        $this->command->info("   Frais de service : à la charge du client");
        $this->command->info("   Billets : Standard 200 XAF x150, VIP 500 XAF x50");
        $this->command->info("   Slug : {$event->slug} (#{$event->id})");
    }
}
