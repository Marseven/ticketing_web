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
        // Créer les rôles s'ils n'existent pas
        $clientRole = Role::firstOrCreate(['slug' => Role::CLIENT], [
            'name' => 'Client',
            'level' => 1,
            'description' => 'Utilisateur client standard'
        ]);

        $organizerRole = Role::firstOrCreate(['slug' => Role::ORGANIZER], [
            'name' => 'Organisateur',
            'level' => 2,
            'description' => 'Organisateur d\'événements'
        ]);

        $adminRole = Role::firstOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Administrateur',
            'level' => 3,
            'description' => 'Administrateur système'
        ]);

        // Créer les utilisateurs de test
        $users = [
            [
                'name' => 'Utilisateur Test',
                'email' => 'user@test.com',
                'phone' => '+241012345678',
                'password' => Hash::make('user123'),
                'is_organizer' => false,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'role' => $clientRole
            ],
            [
                'name' => 'Organisateur Test',
                'email' => 'organizer@test.com',
                'phone' => '+241078901234',
                'password' => Hash::make('organizer123'),
                'is_organizer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'role' => $organizerRole
            ],
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'phone' => '+241065432100',
                'password' => Hash::make('admin123'),
                'is_organizer' => true,
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'role' => $adminRole
            ],
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

        $this->command->info('Utilisateurs de test créés avec succès !');
        $this->command->info('Comptes disponibles :');
        $this->command->info('- user@test.com / +241012345678 : user123 (Client)');
        $this->command->info('- organizer@test.com / +241078901234 : organizer123 (Organisateur)');
        $this->command->info('- admin@test.com / +241065432100 : admin123 (Admin)');
    }
}