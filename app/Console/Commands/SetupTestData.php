<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:test-data {--fresh : Run fresh migrations before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup test data for the ticketing application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configuration des données de test pour Primea Ticketing');
        $this->info('==========================================================');

        // Optionnel : migrations fraîches
        if ($this->option('fresh')) {
            $this->info('📦 Exécution des migrations fraîches...');
            Artisan::call('migrate:fresh');
            $this->info('✅ Migrations terminées');
        }

        // Exécuter les seeders
        $this->info('🌱 Génération des données de test...');
        
        try {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TestDataSeeder'
            ]);
            
            $this->info('✅ Données de test créées avec succès !');
            
            $this->newLine();
            $this->info('🔐 Comptes de connexion disponibles :');
            $this->table(
                ['Rôle', 'Email', 'Téléphone', 'Mot de passe'],
                [
                    ['Client', 'user@test.com', '+241012345678', 'user123'],
                    ['Organisateur', 'organizer@test.com', '+241078901234', 'organizer123'],
                    ['Admin', 'admin@test.com', '+241065432100', 'admin123'],
                ]
            );
            
            $this->newLine();
            $this->info('🎪 Événements créés :');
            $this->line('• 7 événements répartis sur 2, 3 et 6 mois');
            $this->line('• Festivals, concerts, théâtre, conférences et sport');
            $this->line('• Types de billets variés avec prix en FCFA');
            $this->line('• Lieux au Gabon (Libreville et Port-Gentil)');
            
            $this->newLine();
            $this->info('📱 Pour tester l\'application :');
            $this->line('1. Démarrez le serveur : php artisan serve');
            $this->line('2. Compilez les assets : npm run dev');
            $this->line('3. Visitez http://localhost:8000');
            $this->line('4. Connectez-vous avec un des comptes ci-dessus');
            
            $this->newLine();
            $this->info('🚀 Application prête pour les tests !');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la génération des données : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}