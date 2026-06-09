<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(abstract: \Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'create-user',
            'edit-user',
            'delete-user',
            'create-role',
            'edit-role',
            'delete-role',
            'create-permission',
            'edit-permission',
            'delete-permission',
            'create-client',
            'edit-client',
            'delete-client',
            'create-mandate',
            'edit-mandate',
            'delete-mandate',
            'view-mandate',
            'view-client',
        ];

        foreach ($permissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }
        }

        $superAdmin = Role::findByName('super-admin') ?? Role::create(['name' => 'super-admin']);
        $superAdmin->syncPermissions($permissions);

        $customer = Role::findByName('customer') ?? Role::create(['name' => 'customer']);
        $customer->syncPermissions(['create-mandate']);

        foreach (User::all() as $user) {
            if (!$user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
            }
        }
    }
}
