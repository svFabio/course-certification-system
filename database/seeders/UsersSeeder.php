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

        $instructors = [
            [
                'name' => 'Adan Alberto Llanos Zela',
                'email' => 'instructor@umss.edu.bo',
            ],
            [
                'name' => 'Santos Flores Apaza',
                'email' => 'santos.flores@umss.edu.bo',
            ],
            [
                'name' => 'Beatriz Murillo Rojas',
                'email' => 'beatriz.murillo@umss.edu.bo',
            ],
            [
                'name' => 'Carlos Eduardo Vargas Torrico',
                'email' => 'carlos.vargas@umss.edu.bo',
            ],
        ];

        foreach ($instructors as $instData) {
            $inst = User::firstOrCreate(
                ['email' => $instData['email']],
                [
                    'name' => $instData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $inst->syncRoles(['instructor']);
        }
    }
}
