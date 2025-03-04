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
        // Exécuter les seeders dans le bon ordre
        $this->call([
            RolesAndPermissionsSeeder::class, // Créer d'abord les rôles et permissions
            AdminUserSeeder::class,           // Ensuite créer l'utilisateur admin avec le rôle approprié
            CategorySeeder::class,            // Seeder de catégories existant
            // Autres seeders au besoin
        ]);
    }
}
