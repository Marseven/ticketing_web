<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les rôles créés par RoleSeeder
        $clientRole = Role::where('slug', Role::CLIENT)->first();
        $organizerRole = Role::where('slug', Role::ORGANIZER)->first();
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        // Si les rôles n'existent pas, créer des rôles par défaut
        if (!$clientRole) {
            $clientRole = Role::firstOrCreate(['slug' => Role::CLIENT], [
                'name' => 'Client',
                'level' => 1,
                'description' => 'Utilisateur client standard'
            ]);
        }

        if (!$organizerRole) {
            $organizerRole = Role::firstOrCreate(['slug' => Role::ORGANIZER], [
                'name' => 'Organisateur',
                'level' => 2,
                'description' => 'Organisateur d\'événements'
            ]);
        }

        if (!$superAdminRole) {
            $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], [
                'name' => 'Super Admin',
                'level' => 100,
                'description' => 'Accès complet au système'
            ]);
        }

        // Créer les utilisateurs de production
        $users = [
            // Administrateur principal
            [
                'name' => 'Admin Primea',
                'email' => 'admin@primea.ga',
                'phone' => '+241011223344',
                'password' => Hash::make('AdminPrimea2025!'),
                'is_organizer' => false,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => null,
                'role' => $superAdminRole
            ],
            // Organisateurs
            [
                'name' => 'Marie Nzougou',
                'email' => 'marie@primea.ga',
                'phone' => '+241077889900',
                'password' => Hash::make('Organizer2025!'),
                'is_organizer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => null,
                'role' => $organizerRole
            ],
            [
                'name' => 'Jean Mbeng',
                'email' => 'jean@primea.ga',
                'phone' => '+241066554433',
                'password' => Hash::make('Organizer2025!'),
                'is_organizer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => null,
                'role' => $organizerRole
            ],
            // Clients test
            [
                'name' => 'Alice Mboma',
                'email' => 'alice@example.com',
                'phone' => '+241055667788',
                'password' => Hash::make('Client2025!'),
                'is_organizer' => false,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => null,
                'role' => $clientRole
            ],
            [
                'name' => 'Paul Nguema',
                'email' => 'paul@example.com',
                'phone' => '+241044556677',
                'password' => Hash::make('Client2025!'),
                'is_organizer' => false,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => null,
                'role' => $clientRole
            ]
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assigner le rôle
            if (!$user->hasRole($role->slug)) {
                $user->roles()->attach($role->id, [
                    'assigned_at' => now(),
                    'assigned_by' => null,
                ]);
            }
        }

        $this->command->info('Utilisateurs créés avec succès !');
        $this->command->info('Comptes disponibles :');
        $this->command->info('- admin@primea.ga / +241011223344 : AdminPrimea2025! (Super Admin)');
        $this->command->info('- marie@primea.ga / +241077889900 : Organizer2025! (Organisateur)');
        $this->command->info('- jean@primea.ga / +241066554433 : Organizer2025! (Organisateur)');
        $this->command->info('- alice@example.com / +241055667788 : Client2025! (Client)');
        $this->command->info('- paul@example.com / +241044556677 : Client2025! (Client)');
    }
}