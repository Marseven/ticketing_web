<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venue;
use App\Models\Organizer;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer le premier organisateur ou en créer un par défaut
        $organizer = Organizer::first();
        
        if (!$organizer) {
            $organizer = Organizer::create([
                'name' => 'Default Organizer',
                'slug' => 'default-organizer',
                'bio' => 'Organisateur par défaut',
                'contact_email' => 'default@example.com',
                'contact_phone' => '+24100000000',
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        $venues = [
            [
                'name' => 'Stade Omar Bongo',
                'description' => 'Stade national du Gabon, accueille les grands événements sportifs et concerts internationaux',
                'city' => 'Libreville',
                'address' => 'Boulevard Triomphal Omar Bongo Ondimba',
                'postal_code' => 'BP 1234',
                'country' => 'Gabon',
                'capacity' => 40000,
                'phone' => '+241 01 74 00 00',
                'email' => 'info@stade-omarbongo.ga',
                'status' => 'active',
                'geo_lat' => 0.4162,
                'geo_lng' => 9.4673,
            ],
            [
                'name' => 'Stade d\'Angondjé',
                'description' => 'Stade moderne construit pour la CAN 2017, infrastructure de classe internationale',
                'city' => 'Libreville',
                'address' => 'Angondjé, Route de Ntoum',
                'postal_code' => 'BP 5678',
                'country' => 'Gabon',
                'capacity' => 20000,
                'phone' => '+241 01 74 00 01',
                'email' => 'contact@stade-angondje.ga',
                'status' => 'active',
                'geo_lat' => 0.5333,
                'geo_lng' => 9.6333,
            ],
            [
                'name' => 'Institut Français du Gabon',
                'description' => 'Centre culturel proposant spectacles, expositions et projections',
                'city' => 'Libreville',
                'address' => 'Boulevard du Bord de Mer',
                'postal_code' => 'BP 2105',
                'country' => 'Gabon',
                'capacity' => 800,
                'phone' => '+241 01 76 15 36',
                'email' => 'contact@institutfrancais-gabon.com',
                'status' => 'active',
                'geo_lat' => 0.3948,
                'geo_lng' => 9.4496,
            ],
            [
                'name' => 'Palais des Sports',
                'description' => 'Salle polyvalente pour événements sportifs et culturels',
                'city' => 'Libreville',
                'address' => 'Centre Ville, Avenue du Colonel Parant',
                'postal_code' => 'BP 3456',
                'country' => 'Gabon',
                'capacity' => 5000,
                'phone' => '+241 01 74 30 00',
                'email' => 'palais.sports@sports.ga',
                'status' => 'active',
                'geo_lat' => 0.3924,
                'geo_lng' => 9.4536,
            ],
            [
                'name' => 'Place de l\'Indépendance',
                'description' => 'Espace public pour grands rassemblements et célébrations nationales',
                'city' => 'Libreville',
                'address' => 'Centre Ville, Boulevard de l\'Indépendance',
                'postal_code' => 'BP 1000',
                'country' => 'Gabon',
                'capacity' => 15000,
                'phone' => '+241 01 74 50 00',
                'email' => 'mairie@libreville.ga',
                'status' => 'active',
                'geo_lat' => 0.3903,
                'geo_lng' => 9.4542,
            ],
            [
                'name' => 'Stade de Port-Gentil',
                'description' => 'Principal stade de la capitale économique, rénové pour la CAN 2017',
                'city' => 'Port-Gentil',
                'address' => 'Avenue Savorgnan de Brazza',
                'postal_code' => 'BP 789',
                'country' => 'Gabon',
                'capacity' => 20000,
                'phone' => '+241 01 55 00 00',
                'email' => 'stade@portgentil.ga',
                'status' => 'active',
                'geo_lat' => -0.7193,
                'geo_lng' => 8.7823,
            ],
            [
                'name' => 'Centre Culturel Français',
                'description' => 'Espace culturel pour spectacles, concerts et expositions à Port-Gentil',
                'city' => 'Port-Gentil',
                'address' => 'Quartier Grand Village',
                'postal_code' => 'BP 456',
                'country' => 'Gabon',
                'capacity' => 500,
                'phone' => '+241 01 55 20 00',
                'email' => 'ccf@portgentil.ga',
                'status' => 'active',
                'geo_lat' => -0.7167,
                'geo_lng' => 8.7833,
            ],
            [
                'name' => 'Esplanade du Bord de Mer',
                'description' => 'Espace en plein air face à l\'océan, idéal pour festivals et concerts',
                'city' => 'Libreville',
                'address' => 'Boulevard du Bord de Mer',
                'postal_code' => 'BP 2000',
                'country' => 'Gabon',
                'capacity' => 10000,
                'phone' => '+241 01 74 60 00',
                'email' => 'esplanade@libreville.ga',
                'status' => 'active',
                'geo_lat' => 0.3873,
                'geo_lng' => 9.4473,
            ],
            [
                'name' => 'Université Omar Bongo - Amphithéâtre',
                'description' => 'Grand amphithéâtre universitaire pour conférences et événements académiques',
                'city' => 'Libreville',
                'address' => 'Boulevard Léon Mba, Campus Universitaire',
                'postal_code' => 'BP 13131',
                'country' => 'Gabon',
                'capacity' => 1200,
                'phone' => '+241 01 73 20 58',
                'email' => 'amphi@uob.ga',
                'status' => 'active',
                'geo_lat' => 0.4333,
                'geo_lng' => 9.4333,
            ],
            [
                'name' => 'Stade de Franceville',
                'description' => 'Stade moderne construit pour la CAN, au cœur du Haut-Ogooué',
                'city' => 'Franceville',
                'address' => 'Route de Mvengué',
                'postal_code' => 'BP 321',
                'country' => 'Gabon',
                'capacity' => 20000,
                'phone' => '+241 01 67 00 00',
                'email' => 'stade@franceville.ga',
                'status' => 'active',
                'geo_lat' => -1.6328,
                'geo_lng' => 13.5828,
            ],
            [
                'name' => 'Parc de la Lékédi',
                'description' => 'Parc naturel offrant un cadre unique pour événements éco-culturels',
                'city' => 'Bakoumba',
                'address' => 'Route de Bakoumba, à 5km du centre',
                'postal_code' => 'BP 12',
                'country' => 'Gabon',
                'capacity' => 2000,
                'phone' => '+241 01 67 50 00',
                'email' => 'parc.lekedi@tourisme.ga',
                'status' => 'active',
                'geo_lat' => -1.6833,
                'geo_lng' => 12.9667,
            ],
            [
                'name' => 'Centre des Affaires Sociales',
                'description' => 'Centre polyvalent pour événements sociaux et culturels',
                'city' => 'Libreville',
                'address' => 'Quartier Glass, Rue des Missions',
                'postal_code' => 'BP 4567',
                'country' => 'Gabon',
                'capacity' => 3000,
                'phone' => '+241 01 74 80 00',
                'email' => 'cas@social.ga',
                'status' => 'active',
                'geo_lat' => 0.4167,
                'geo_lng' => 9.4667,
            ],
        ];

        foreach ($venues as $venueData) {
            Venue::updateOrCreate(
                [
                    'name' => $venueData['name'],
                    'city' => $venueData['city']
                ],
                array_merge($venueData, ['organizer_id' => $organizer->id])
            );
        }
    }
}