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
        $permissions = [
            'manage-courses', 'manage-groups', 'manage-preinscriptions', 'manage-payments',
            'manage-sessions', 'manage-attendances', 'manage-evaluations', 'manage-certificates',
            'view-reports', 'manage-users',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $instructor = Role::firstOrCreate(['name' => 'instructor']);
        $instructor->givePermissionTo(['manage-sessions', 'manage-attendances', 'manage-evaluations']);

        Role::firstOrCreate(['name' => 'student']);
    }
}
