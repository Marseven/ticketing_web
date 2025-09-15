<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Festival',
                'slug' => 'festival',
                'description' => 'Festivals de musique et événements culturels',
                'icon' => 'musical-note',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Concert',
                'slug' => 'concert',
                'description' => 'Concerts et spectacles musicaux',
                'icon' => 'microphone',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Théâtre',
                'slug' => 'theater',
                'description' => 'Pièces de théâtre et spectacles',
                'icon' => 'face-smile',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Conférence',
                'slug' => 'conference',
                'description' => 'Conférences et événements professionnels',
                'icon' => 'academic-cap',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Sport',
                'slug' => 'sport',
                'description' => 'Événements sportifs et compétitions',
                'icon' => 'trophy',
                'color' => '#10B981',
            ],
            [
                'name' => 'Exposition',
                'slug' => 'exhibition',
                'description' => 'Expositions et galeries d\'art',
                'icon' => 'photo',
                'color' => '#EC4899',
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('Catégories créées avec succès !');
    }
}
