<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Models\Organizer;
use App\Models\Schedule;
use App\Models\TicketType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestEventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les organisateurs
        $marieUser = User::where('email', 'marie@primea.ga')->first();
        $jeanUser = User::where('email', 'jean@primea.ga')->first();
        
        if (!$marieUser || !$jeanUser) {
            $this->command->error('Veuillez d\'abord exécuter le seeder TestUsersSeeder');
            return;
        }

        // Créer les organisateurs
        $primeatOrganizer = Organizer::firstOrCreate(
            ['name' => 'Primea Events Gabon'],
            [
                'description' => 'Organisateur principal d\'événements culturels au Gabon',
                'email' => 'contact@primea-events.ga',
                'phone' => '+241011111111',
                'website' => 'https://primea-events.ga',
                'status' => 'active',
                'created_by' => $marieUser->id,
            ]
        );
        
        $sportOrganizer = Organizer::firstOrCreate(
            ['name' => 'Sport Events Gabon'],
            [
                'description' => 'Spécialisé dans l\'organisation d\'événements sportifs',
                'email' => 'sport@primea-events.ga',
                'phone' => '+241022222222',
                'website' => 'https://sport-events.ga',
                'status' => 'active',
                'created_by' => $jeanUser->id,
            ]
        );

        // Associer les utilisateurs aux organisateurs
        if (!$marieUser->organizers()->where('organizer_id', $primeatOrganizer->id)->exists()) {
            $marieUser->organizers()->attach($primeatOrganizer->id, [
                'role' => 'admin',
                'permissions' => json_encode(['create_events', 'manage_events', 'view_reports']),
            ]);
        }
        
        if (!$jeanUser->organizers()->where('organizer_id', $sportOrganizer->id)->exists()) {
            $jeanUser->organizers()->attach($sportOrganizer->id, [
                'role' => 'admin', 
                'permissions' => json_encode(['create_events', 'manage_events', 'view_reports']),
            ]);
        }

        // Calculer les dates pour les prochains mois
        $now = Carbon::now();
        
        $events = [
            // Événements dans 2 mois
            [
                'title' => 'Festival Électro Gabonais 2025',
                'description' => 'Le plus grand festival de musique électronique du Gabon avec des DJs internationaux et locaux. Une nuit de folie garantie avec les meilleurs artistes électroniques d\'Afrique centrale !',
                'category' => 'festival',
                'venue_name' => 'Stade d\'Angondjé',
                'venue_address' => 'Quartier d\'Angondjé',
                'venue_city' => 'Libreville',
                'venue_latitude' => 0.3656,
                'venue_longitude' => 9.4681,
                'image_url' => 'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 5000,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(2)->setDay(15)->setHour(20)->setMinute(0),
                'end_date' => $now->copy()->addMonths(2)->setDay(16)->setHour(4)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Early Bird',
                        'description' => 'Tarif préférentiel - accès général',
                        'price' => 12000,
                        'quantity' => 2000,
                        'max_per_order' => 10,
                        'sale_starts_at' => $now->copy()->subDays(30),
                        'sale_ends_at' => $now->copy()->addMonths(2)->setDay(15)->setHour(18),
                    ],
                    [
                        'name' => 'VIP',
                        'description' => 'Accès VIP + backstage + boissons incluses',
                        'price' => 35000,
                        'quantity' => 500,
                        'max_per_order' => 5,
                        'sale_starts_at' => $now->copy()->subDays(30),
                        'sale_ends_at' => $now->copy()->addMonths(2)->setDay(15)->setHour(18),
                    ],
                ]
            ],
            [
                'title' => 'Concert Afrobeat Live',
                'description' => 'Une soirée dédiée à l\'afrobeat avec des artistes locaux et internationaux. Danse et musique garanties ! Venez découvrir les sons authentiques de l\'Afrique moderne.',
                'category' => 'concert',
                'venue_name' => 'Palais des Sports',
                'venue_address' => 'Boulevard Triomphal',
                'venue_city' => 'Port-Gentil',
                'venue_latitude' => -0.7193,
                'venue_longitude' => 8.7815,
                'image_url' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 2000,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(2)->setDay(22)->setHour(21)->setMinute(0),
                'end_date' => $now->copy()->addMonths(2)->setDay(23)->setHour(2)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Standard',
                        'description' => 'Accès général à la soirée',
                        'price' => 10000,
                        'quantity' => 1500,
                        'max_per_order' => 8,
                        'sale_starts_at' => $now->copy()->subDays(25),
                        'sale_ends_at' => $now->copy()->addMonths(2)->setDay(22)->setHour(19),
                    ],
                    [
                        'name' => 'VIP',
                        'description' => 'Accès VIP avec boissons et zone privilégiée',
                        'price' => 25000,
                        'quantity' => 300,
                        'max_per_order' => 4,
                        'sale_starts_at' => $now->copy()->subDays(25),
                        'sale_ends_at' => $now->copy()->addMonths(2)->setDay(22)->setHour(19),
                    ],
                ]
            ],
            
            // Événements dans 3 mois
            [
                'title' => 'Théâtre: Les Misérables',
                'description' => 'Une adaptation moderne du chef-d\'œuvre de Victor Hugo par la troupe nationale. Une expérience théâtrale inoubliable qui vous transportera dans le Paris du XIXe siècle.',
                'category' => 'theater',
                'venue_name' => 'Théâtre National',
                'venue_address' => 'Boulevard de l\'Indépendance',
                'venue_city' => 'Libreville',
                'venue_latitude' => 0.3839,
                'venue_longitude' => 9.4577,
                'image_url' => 'https://images.unsplash.com/photo-1507924538820-ede94a04019d?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 400,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(3)->setDay(8)->setHour(19)->setMinute(0),
                'end_date' => $now->copy()->addMonths(3)->setDay(8)->setHour(22)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Orchestre',
                        'description' => 'Places d\'orchestre - vue optimale',
                        'price' => 12000,
                        'quantity' => 200,
                        'max_per_order' => 6,
                        'sale_starts_at' => $now->copy()->subDays(20),
                        'sale_ends_at' => $now->copy()->addMonths(3)->setDay(8)->setHour(17),
                    ],
                    [
                        'name' => 'Balcon',
                        'description' => 'Places au balcon - excellente acoustique',
                        'price' => 18000,
                        'quantity' => 150,
                        'max_per_order' => 4,
                        'sale_starts_at' => $now->copy()->subDays(20),
                        'sale_ends_at' => $now->copy()->addMonths(3)->setDay(8)->setHour(17),
                    ],
                ]
            ],
            [
                'title' => 'Conférence Tech Innovation 2025',
                'description' => 'La plus grande conférence technologique de l\'année avec des experts internationaux partageant les dernières innovations en IA, blockchain, IoT et transformation digitale en Afrique.',
                'category' => 'conference',
                'venue_name' => 'Centre de Conférences Omar Bongo',
                'venue_address' => 'Avenue du Général de Gaulle',
                'venue_city' => 'Libreville',
                'venue_latitude' => 0.3924,
                'venue_longitude' => 9.4538,
                'image_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => false,
                'max_capacity' => 800,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(3)->setDay(20)->setHour(9)->setMinute(0),
                'end_date' => $now->copy()->addMonths(3)->setDay(20)->setHour(17)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Standard',
                        'description' => 'Accès aux conférences et pauses café',
                        'price' => 25000,
                        'quantity' => 500,
                        'max_per_order' => 5,
                        'sale_starts_at' => $now->copy()->subDays(15),
                        'sale_ends_at' => $now->copy()->addMonths(3)->setDay(20)->setHour(7),
                    ],
                    [
                        'name' => 'Premium',
                        'description' => 'Accès conférences + networking lunch + workshops',
                        'price' => 45000,
                        'quantity' => 200,
                        'max_per_order' => 3,
                        'sale_starts_at' => $now->copy()->subDays(15),
                        'sale_ends_at' => $now->copy()->addMonths(3)->setDay(20)->setHour(7),
                    ],
                ]
            ],
            
            // Événements dans 6 mois
            [
                'title' => 'Festival des Arts Urbains Libreville',
                'description' => 'Un festival unique célébrant la culture urbaine avec des spectacles de danse, rap, graffiti, breakdance et bien plus encore. Street art et performances live dans toute la ville !',
                'category' => 'festival',
                'venue_name' => 'Place de l\'Indépendance',
                'venue_address' => 'Place de l\'Indépendance',
                'venue_city' => 'Libreville',
                'venue_latitude' => 0.3937,
                'venue_longitude' => 9.4543,
                'image_url' => 'https://images.unsplash.com/photo-1493676304819-0d7a8d026dcf?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 3000,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(6)->setDay(10)->setHour(14)->setMinute(0),
                'end_date' => $now->copy()->addMonths(6)->setDay(10)->setHour(22)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Pass Journée',
                        'description' => 'Accès pour toute la journée à tous les spectacles',
                        'price' => 8000,
                        'quantity' => 2500,
                        'max_per_order' => 10,
                        'sale_starts_at' => $now->copy()->subDays(10),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(10)->setHour(12),
                    ],
                    [
                        'name' => 'Pass Artiste',
                        'description' => 'Accès VIP + rencontre avec les artistes + goodies',
                        'price' => 20000,
                        'quantity' => 200,
                        'max_per_order' => 4,
                        'sale_starts_at' => $now->copy()->subDays(10),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(10)->setHour(12),
                    ],
                ]
            ],
            [
                'title' => 'Match de Gala - Lions vs Panthères',
                'description' => 'Le match de l\'année ! Un affrontement spectaculaire entre les Lions de l\'Estuaire et les Panthères du Haut-Ogooué. Venez supporter votre équipe favorite dans ce derby gabonais !',
                'category' => 'sport',
                'venue_name' => 'Stade d\'Angondjé',
                'venue_address' => 'Quartier d\'Angondjé',
                'venue_city' => 'Libreville',
                'venue_latitude' => 0.3656,
                'venue_longitude' => 9.4681,
                'image_url' => 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 10000,
                'organizer_id' => $sportOrganizer->id,
                'created_by' => $jeanUser->id,
                'start_date' => $now->copy()->addMonths(6)->setDay(25)->setHour(15)->setMinute(0),
                'end_date' => $now->copy()->addMonths(6)->setDay(25)->setHour(17)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Tribune populaire',
                        'description' => 'Places en tribune populaire - ambiance garantie',
                        'price' => 5000,
                        'quantity' => 8000,
                        'max_per_order' => 15,
                        'sale_starts_at' => $now->copy()->subDays(5),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(25)->setHour(13),
                    ],
                    [
                        'name' => 'Tribune VIP',
                        'description' => 'Places VIP avec rafraîchissements et parking',
                        'price' => 20000,
                        'quantity' => 1000,
                        'max_per_order' => 8,
                        'sale_starts_at' => $now->copy()->subDays(5),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(25)->setHour(13),
                    ],
                ]
            ],
            [
                'title' => 'Festival de Jazz du Golfe de Guinée',
                'description' => 'Un festival de jazz exceptionnel réunissant les plus grands noms du jazz africain et international. Trois jours de concerts dans un cadre magique au bord de l\'océan.',
                'category' => 'festival',
                'venue_name' => 'Amphithéâtre de la Marina',
                'venue_address' => 'Boulevard de la Marina',
                'venue_city' => 'Port-Gentil',
                'venue_latitude' => -0.7193,
                'venue_longitude' => 8.7815,
                'image_url' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
                'status' => 'published',
                'is_active' => true,
                'is_featured' => true,
                'max_capacity' => 1200,
                'organizer_id' => $primeatOrganizer->id,
                'created_by' => $marieUser->id,
                'start_date' => $now->copy()->addMonths(6)->setDay(5)->setHour(18)->setMinute(0),
                'end_date' => $now->copy()->addMonths(6)->setDay(7)->setHour(23)->setMinute(0),
                'tickets' => [
                    [
                        'name' => 'Pass 1 jour',
                        'description' => 'Accès pour une journée au choix',
                        'price' => 15000,
                        'quantity' => 400,
                        'max_per_order' => 6,
                        'sale_starts_at' => $now->copy(),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(5)->setHour(16),
                    ],
                    [
                        'name' => 'Pass 3 jours',
                        'description' => 'Accès pour les 3 jours du festival',
                        'price' => 35000,
                        'quantity' => 300,
                        'max_per_order' => 4,
                        'sale_starts_at' => $now->copy(),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(5)->setHour(16),
                    ],
                    [
                        'name' => 'VIP 3 jours',
                        'description' => 'Accès VIP + rencontres artistes + dîner exclusif',
                        'price' => 75000,
                        'quantity' => 100,
                        'max_per_order' => 2,
                        'sale_starts_at' => $now->copy(),
                        'sale_ends_at' => $now->copy()->addMonths(6)->setDay(5)->setHour(16),
                    ],
                ]
            ],
        ];

        foreach ($events as $eventData) {
            $tickets = $eventData['tickets'];
            $startDate = $eventData['start_date'];
            $endDate = $eventData['end_date'];
            
            unset($eventData['tickets'], $eventData['start_date'], $eventData['end_date']);

            $event = Event::updateOrCreate(
                ['title' => $eventData['title']],
                $eventData
            );

            // Créer le schedule
            Schedule::updateOrCreate(
                ['event_id' => $event->id],
                [
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'timezone' => 'Africa/Libreville',
                    'created_by' => $organizerUser->id,
                ]
            );

            // Créer les types de tickets
            foreach ($tickets as $ticketData) {
                TicketType::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'name' => $ticketData['name']
                    ],
                    array_merge($ticketData, [
                        'event_id' => $event->id,
                        'is_active' => true,
                        'created_by' => $marieUser->id,
                    ])
                );
            }
        }

        $this->command->info('Événements de test créés avec succès !');
        $this->command->info('7 événements ont été créés pour les 2, 3 et 6 prochains mois.');
    }
}