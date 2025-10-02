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
                'name' => 'Musique',
                'description' => 'Concerts et spectacles musicaux',
                'color' => '#e74c3c',
                'is_active' => true,
            ],
            [
                'name' => 'Culture',
                'description' => 'Événements culturels et artistiques',
                'color' => '#9b59b6',
                'is_active' => true,
            ],
            [
                'name' => 'Gastronomie',
                'description' => 'Événements culinaires et dégustations',
                'color' => '#d35400',
                'is_active' => true,
            ],
            [
                'name' => 'Sport',
                'description' => 'Événements sportifs et compétitions',
                'color' => '#2ecc71',
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'description' => 'Conférences, séminaires et événements professionnels',
                'color' => '#34495e',
                'is_active' => true,
            ],
            [
                'name' => 'Autres',
                'description' => 'Autres types d\'événements',
                'color' => '#95a5a6',
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