<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Concerts',
                'description' => 'Concerts et spectacles musicaux en direct',
                'color' => '#e74c3c',
                'is_active' => true,
            ],
            [
                'name' => 'Festival',
                'description' => 'Festivals de musique, culture et arts',
                'color' => '#3498db',
                'is_active' => true,
            ],
            [
                'name' => 'Théâtre',
                'description' => 'Pièces de théâtre et spectacles dramatiques',
                'color' => '#9b59b6',
                'is_active' => true,
            ],
            [
                'name' => 'Sports',
                'description' => 'Événements sportifs et compétitions',
                'color' => '#2ecc71',
                'is_active' => true,
            ],
            [
                'name' => 'Conférences',
                'description' => 'Conférences, séminaires et formations',
                'color' => '#34495e',
                'is_active' => true,
            ],
            [
                'name' => 'Expositions',
                'description' => 'Expositions d\'art, salons et foires',
                'color' => '#16a085',
                'is_active' => true,
            ],
            [
                'name' => 'Cinéma',
                'description' => 'Projections de films et avant-premières',
                'color' => '#f39c12',
                'is_active' => true,
            ],
            [
                'name' => 'Gastronomie',
                'description' => 'Événements culinaires et dégustations',
                'color' => '#d35400',
                'is_active' => true,
            ],
            [
                'name' => 'Enfants',
                'description' => 'Spectacles et activités pour enfants',
                'color' => '#e91e63',
                'is_active' => true,
            ],
            [
                'name' => 'Culture Traditionnelle',
                'description' => 'Événements culturels traditionnels gabonais',
                'color' => '#795548',
                'is_active' => true,
            ],
            [
                'name' => 'Soirées & Galas',
                'description' => 'Soirées dansantes, galas et réceptions',
                'color' => '#673ab7',
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'description' => 'Networking, salons professionnels et B2B',
                'color' => '#607d8b',
                'is_active' => true,
            ],
            [
                'name' => 'Religieux',
                'description' => 'Événements religieux et spirituels',
                'color' => '#009688',
                'is_active' => true,
            ],
            [
                'name' => 'Tourisme',
                'description' => 'Visites guidées et activités touristiques',
                'color' => '#4caf50',
                'is_active' => true,
            ],
            [
                'name' => 'Éducation',
                'description' => 'Ateliers éducatifs et programmes scolaires',
                'color' => '#ff5722',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => Str::slug($categoryData['name'])],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'color' => $categoryData['color'],
                    'is_active' => $categoryData['is_active'],
                ]
            );
        }
    }
}