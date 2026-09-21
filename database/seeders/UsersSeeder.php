<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@umss.edu.bo'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $instructor = User::firstOrCreate(
            ['email' => 'instructor@umss.edu.bo'],
            [
                'name' => 'Instructor Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $instructor->assignRole('instructor');
    }
}
