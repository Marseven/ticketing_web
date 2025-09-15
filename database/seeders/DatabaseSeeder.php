<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appeler le seeder des rôles en premier
        $this->call([
            RoleSeeder::class,
        ]);

        // Créer un utilisateur admin par défaut
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@primea.com',
            'is_organizer' => true,
        ]);
        $admin->assignRole('admin');

        // Créer un organisateur de test
        $organizer = User::factory()->create([
            'name' => 'Organisateur Test',
            'email' => 'organizer@primea.com',
            'is_organizer' => true,
        ]);
        $organizer->assignRole('organizer');

        // Créer un client de test
        $client = User::factory()->create([
            'name' => 'Client Test',
            'email' => 'client@primea.com',
            'is_organizer' => false,
        ]);
        $client->assignRole('client');
    }
}
