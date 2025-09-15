<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Génération des données de test...');
        
        // Créer les utilisateurs de test
        $this->call(TestUsersSeeder::class);
        
        // Créer les événements de test
        $this->call(TestEventsSeeder::class);
        
        $this->command->info('✅ Données de test générées avec succès !');
        $this->command->info('');
        $this->command->info('🔐 Comptes de connexion disponibles :');
        $this->command->info('┌─────────────────────────────────────────────────────────────────┐');
        $this->command->info('│ UTILISATEUR CLIENT                                              │');
        $this->command->info('│ Email: user@test.com                                           │');
        $this->command->info('│ Téléphone: +241012345678                                       │');
        $this->command->info('│ Mot de passe: user123                                          │');
        $this->command->info('├─────────────────────────────────────────────────────────────────┤');
        $this->command->info('│ ORGANISATEUR                                                    │');
        $this->command->info('│ Email: organizer@test.com                                      │');
        $this->command->info('│ Téléphone: +241078901234                                       │');
        $this->command->info('│ Mot de passe: organizer123                                     │');
        $this->command->info('├─────────────────────────────────────────────────────────────────┤');
        $this->command->info('│ ADMINISTRATEUR                                                  │');
        $this->command->info('│ Email: admin@test.com                                          │');
        $this->command->info('│ Téléphone: +241065432100                                       │');
        $this->command->info('│ Mot de passe: admin123                                         │');
        $this->command->info('└─────────────────────────────────────────────────────────────────┘');
        $this->command->info('');
        $this->command->info('🎪 Événements créés :');
        $this->command->info('• 7 événements répartis sur 2, 3 et 6 mois');
        $this->command->info('• Festivals, concerts, théâtre, conférences et sport');
        $this->command->info('• Types de billets variés avec prix en FCFA');
        $this->command->info('• Lieux au Gabon (Libreville et Port-Gentil)');
        $this->command->info('');
        $this->command->info('🚀 Vous pouvez maintenant tester l\'application !');
    }
}