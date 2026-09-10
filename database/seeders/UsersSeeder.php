<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@umss.edu.bo',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $instructor = User::create([
            'name' => 'Instructor Demo',
            'email' => 'instructor@umss.edu.bo',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $instructor->assignRole('instructor');

        $student = User::create([
            'name' => 'Estudiante Demo',
            'email' => 'student@umss.edu.bo',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
    }
}
