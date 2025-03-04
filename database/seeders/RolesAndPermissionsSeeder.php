<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser les rôles et permissions mis en cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        // Permissions pour les produits
        Permission::firstOrCreate(['name' => 'product.view']);
        Permission::firstOrCreate(['name' => 'product.create']);
        Permission::firstOrCreate(['name' => 'product.update']);
        Permission::firstOrCreate(['name' => 'product.delete']);

        // Permissions pour les catégories
        Permission::firstOrCreate(['name' => 'category.view']);
        Permission::firstOrCreate(['name' => 'category.create']);
        Permission::firstOrCreate(['name' => 'category.update']);
        Permission::firstOrCreate(['name' => 'category.delete']);

        // Permissions pour les commandes
        Permission::firstOrCreate(['name' => 'order.view']);
        Permission::firstOrCreate(['name' => 'order.create']);
        Permission::firstOrCreate(['name' => 'order.update']);
        Permission::firstOrCreate(['name' => 'order.delete']);

        // Permissions pour les commentaires
        Permission::firstOrCreate(['name' => 'comment.view']);
        Permission::firstOrCreate(['name' => 'comment.update']);
        Permission::firstOrCreate(['name' => 'comment.delete']);

        // Permissions pour les paniers
        Permission::firstOrCreate(['name' => 'cart.view']);
        Permission::firstOrCreate(['name' => 'cart.create']);
        Permission::firstOrCreate(['name' => 'cart.update']);
        Permission::firstOrCreate(['name' => 'cart.delete']);

        // Permissions pour les médias
        Permission::firstOrCreate(['name' => 'media.update']);
        Permission::firstOrCreate(['name' => 'media.delete']);

        // Permissions pour les paiements
        Permission::firstOrCreate(['name' => 'payment.view']);
        Permission::firstOrCreate(['name' => 'payment.create']);
        Permission::firstOrCreate(['name' => 'payment.update']);
        Permission::firstOrCreate(['name' => 'payment.delete']);

        // Créer les rôles et assigner des permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'product.view',
            'product.create',
            'product.update',
            'category.view',
            'category.create',
            'category.update',
            'order.view',
            'order.update',
            'comment.view',
            'comment.update',
            'comment.delete',
            'cart.view',
            'media.update',
            'payment.view'
        ]);

        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        // Les clients ont des permissions limitées qui seraient généralement gérées par la logique de l'application
    }
}
