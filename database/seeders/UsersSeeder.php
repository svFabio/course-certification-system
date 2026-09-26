<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make(config('app.seed_password'));

        $admin = User::firstOrCreate(
            ['email' => 'admin@umss.edu.bo'],
            [
                'name' => 'Administrador',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(UserRole::ADMIN->value);

        $instructors = [
            ['email' => 'instructor@umss.edu.bo'],
            ['email' => 'instructor2@umss.edu.bo'],
            ['email' => 'instructor3@umss.edu.bo'],
            ['email' => 'instructor4@umss.edu.bo'],
        ];

        foreach ($instructors as $instData) {
            $inst = User::firstOrCreate(
                ['email' => $instData['email']],
                [
                    'name' => fake()->name(),
                    'password' => $password,
                    'email_verified_at' => now(),
                ]
            );
            $inst->syncRoles([UserRole::INSTRUCTOR->value]);
        }

        $student = User::firstOrCreate(
            ['email' => 'student@umss.edu.bo'],
            [
                'name' => 'Estudiante Demo',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );
        $student->syncRoles([UserRole::STUDENT->value]);
    }
}
