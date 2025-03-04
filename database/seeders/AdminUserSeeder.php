<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer l'utilisateur admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        // Si vous utilisez Spatie Laravel-permission, vous pouvez assigner le rôle admin
        // Assurez-vous que le rôle existe déjà ou créez-le ici
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            // Créer le rôle admin s'il n'existe pas
            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

            // Assigner le rôle à l'utilisateur admin
            $admin->assignRole($adminRole);
        }
    }
}
