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
                'name' => 'Vêtements Homme',
                'description' => 'Collection de vêtements traditionnels pour hommes',
                'children' => [
                    ['name' => 'Qamis', 'description' => 'Tuniques longues élégantes et modestes'],
                    ['name' => 'Sarouel', 'description' => 'Pantalons amples et confortables'],
                    ['name' => 'Jabador', 'description' => 'Ensembles traditionnels marocains'],
                    ['name' => 'Djellaba', 'description' => 'Vêtements amples à capuche pour toutes occasions']
                ]
            ],
            [
                'name' => 'Vêtements Femme',
                'description' => 'Collection de tenues élégantes et modestes pour femmes',
                'children' => [
                    ['name' => 'Abaya', 'description' => 'Robes longues et modestes pour toutes occasions'],
                    ['name' => 'Jilbab', 'description' => 'Ensembles avec hijab intégré pour une couverture complète'],
                    ['name' => 'Kimono', 'description' => 'Vestes longues et élégantes'],
                ]
            ],
            [
                'name' => 'Vêtements Enfant',
                'description' => 'Mode islamique pour enfants, confortable et élégante',
                'children' => [
                    ['name' => 'Qamis Enfant', 'description' => 'Qamis traditionnels adaptés aux plus jeunes'],
                    ['name' => 'Abaya Enfant', 'description' => 'Abayas légères et confortables pour petites filles'],
                    ['name' => 'Hijab Enfant', 'description' => 'Hijabs adaptés aux enfants'],
                    ['name' => 'Djellaba Enfant', 'description' => 'Vêtements traditionnels pour enfants']
                ]
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Complétez votre style avec nos accessoires islamiques',
                'children' => [
                    ['name' => 'Châles', 'description' => 'Châles élégants pour compléter vos tenues'],
                    ['name' => 'Ceintures pour Abaya', 'description' => 'Ajoutez une touche d’élégance à votre abaya'],
                    ['name' => 'Gants', 'description' => 'Gants élégants pour une couverture totale'],
                    ['name' => 'Épingles & Broches', 'description' => 'Accessoires pour fixer et styliser votre hijab']
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
