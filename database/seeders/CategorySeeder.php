<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // Catégories principales
        $mainCategories = [
            [
                'name' => 'Hijabs',
                'description' => 'Collection complète de hijabs élégants et modestes',
                'children' => [
                    ['name' => 'Hijab Instant', 'description' => 'Hijabs prêts à porter, faciles à enfiler'],
                    ['name' => 'Hijab Premium', 'description' => 'Hijabs en tissus de haute qualité'],
                    ['name' => 'Hijab Underscarf', 'description' => 'Bonnets et sous-hijabs'],
                    ['name' => 'Hijab Accessoires', 'description' => 'Épingles, broches et accessoires pour hijabs']
                ]
            ],
            [
                'name' => 'Robes',
                'description' => 'Robes modestes et tendance pour toutes les occasions',
                'children' => [
                    ['name' => 'Abaya', 'description' => 'Robes longues traditionnelles'],
                    ['name' => 'Robe de Soirée', 'description' => 'Robes élégantes pour occasions spéciales'],
                    ['name' => 'Robe Casual', 'description' => 'Robes pour le quotidien'],
                    ['name' => 'Robe Kimono', 'description' => 'Robes avec coupe kimono moderne']
                ]
            ],
            [
                'name' => 'Ensemble',
                'description' => 'Coordonnés complets pour un look harmonieux',
                'children' => [
                    ['name' => 'Ensemble 2 Pièces', 'description' => 'Ensemble hijab + robe'],
                    ['name' => 'Ensemble 3 Pièces', 'description' => 'Ensemble complet avec pantalon'],
                    ['name' => 'Kimono Set', 'description' => 'Ensembles avec kimono assorti']
                ]
            ],
            [
                'name' => 'Jupes et Pantalons',
                'description' => 'Bas élégants et confortables',
                'children' => [
                    ['name' => 'Jupe Longue', 'description' => 'Jupes modestes et élégantes'],
                    ['name' => 'Pantalon Large', 'description' => 'Pantalons fluides et confortables'],
                    ['name' => 'Pantalon Palazzo', 'description' => 'Pantalons très amples']
                ]
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Compléments pour parfaire votre tenue',
                'children' => [
                    ['name' => 'Broches', 'description' => 'Broches pour hijabs et vêtements'],
                    ['name' => 'Châles', 'description' => 'Châles légers et élégants'],
                    ['name' => 'Bijoux', 'description' => 'Bijoux discrets et raffinés']
                ]
            ]
        ];

        // Créer les catégories principales et leurs enfants
        foreach ($mainCategories as $mainCategoryData) {
            $children = $mainCategoryData['children'] ?? [];
            unset($mainCategoryData['children']);

            // Créer la catégorie principale
            $mainCategory = Category::create([
                'name' => $mainCategoryData['name'],
                'description' => $mainCategoryData['description'],
                'slug' => Str::slug($mainCategoryData['name']),
                'is_active' => true
            ]);

            // Créer les sous-catégories
            foreach ($children as $childData) {
                Category::create([
                    'name' => $childData['name'],
                    'description' => $childData['description'],
                    'slug' => Str::slug($childData['name']),
                    'parent_id' => $mainCategory->id,
                    'is_active' => true
                ]);
            }
        }
    }
}
