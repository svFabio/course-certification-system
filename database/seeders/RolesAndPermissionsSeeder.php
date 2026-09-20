<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permission::create(['name' => 'manage-courses']);
        Permission::create(['name' => 'manage-groups']);
        Permission::create(['name' => 'manage-preinscriptions']);
        Permission::create(['name' => 'manage-payments']);
        Permission::create(['name' => 'manage-sessions']);
        Permission::create(['name' => 'manage-attendances']);
        Permission::create(['name' => 'manage-evaluations']);
        Permission::create(['name' => 'manage-certificates']);
        Permission::create(['name' => 'view-reports']);
        Permission::create(['name' => 'manage-users']);

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $instructor = Role::create(['name' => 'instructor']);
        $instructor->givePermissionTo([
            'manage-sessions',
            'manage-attendances',
            'manage-evaluations',
        ]);

        Role::create(['name' => 'student']);
    }
}
