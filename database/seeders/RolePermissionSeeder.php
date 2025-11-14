<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-any-task',
            'view-own-task',
            'create-task',
            'update-own-task',
            'update-any-task',
            'delete-own-task',
            'delete-any-task',

            'view-any-user',
            'create-user',
            'update-user',
            'delete-user',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        $adminRole->givePermissionTo(Permission::all());

        $userRole->givePermissionTo([
            'view-own-task',
            'create-task',
            'update-own-task',
            'delete-own-task',
        ]);
    }
}