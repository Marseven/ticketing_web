<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the application's production database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TestUsersSeeder::class,
            TestEventsSeeder::class,
        ]);
        
        $this->command->info('🎉 Base de données de production seedée avec succès !');
        $this->command->info('');
        $this->command->info('=== COMPTES DISPONIBLES ===');
        $this->command->info('👑 ADMIN : admin@primea.ga / AdminPrimea2025!');
        $this->command->info('🏢 ORGANISATEURS :');
        $this->command->info('   - marie@primea.ga / Organizer2025!');
        $this->command->info('   - jean@primea.ga / Organizer2025!');
        $this->command->info('👤 CLIENTS :');
        $this->command->info('   - alice@example.com / Client2025!');
        $this->command->info('   - paul@example.com / Client2025!');
        $this->command->info('');
        $this->command->info('📅 7 événements créés pour les prochains mois');
        $this->command->info('🎯 Application prête pour la production !');
    }
}