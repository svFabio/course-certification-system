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
        $admin = User::create([
            'name' => 'Administrador',
            'email' => env('SEED_ADMIN_EMAIL', 'admin@umss.edu.bo'),
            'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $instructor = User::create([
            'name' => 'Instructor Demo',
            'email' => env('SEED_INSTRUCTOR_EMAIL', 'instructor@umss.edu.bo'),
            'password' => Hash::make(env('SEED_INSTRUCTOR_PASSWORD', 'password')),
            'email_verified_at' => now(),
        ]);
        $instructor->assignRole('instructor');

        $student = User::create([
            'name' => 'Estudiante Demo',
            'email' => env('SEED_STUDENT_EMAIL', 'student@umss.edu.bo'),
            'password' => Hash::make(env('SEED_STUDENT_PASSWORD', 'password')),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
    }
}
