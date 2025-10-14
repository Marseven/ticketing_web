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
                'description' => 'Type d\'utilisateur avec accès à l\'administration (super admin, admin, support)',
                'is_active' => true,
            ],
            [
                'name' => 'client',
                'label' => 'Client',
                'description' => 'Type d\'utilisateur client (organisateur ou client simple)',
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
