<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserType;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userTypes = [
            [
                'name' => 'admin',
                'label' => 'Administrateur',
                'description' => 'Utilisateur avec tous les privilèges d\'administration de la plateforme',
                'is_active' => true,
            ],
            [
                'name' => 'client',
                'label' => 'Client',
                'description' => 'Utilisateur client qui achète des billets pour des événements',
                'is_active' => true,
            ],
            [
                'name' => 'organizer',
                'label' => 'Organisateur',
                'description' => 'Utilisateur qui peut créer et gérer des événements',
                'is_active' => true,
            ],
        ];

        foreach ($userTypes as $userType) {
            UserType::updateOrCreate(
                ['name' => $userType['name']],
                $userType
            );
        }
    }
}
