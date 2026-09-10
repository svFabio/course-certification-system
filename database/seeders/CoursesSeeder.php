<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoursesSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@umss.edu.bo')->first();

        $course = \App\Models\Course::create([
            'nombre' => 'Introducción a la Programación Web',
            'contenido' => 'Curso introductorio a HTML, CSS, JavaScript y PHP.',
            'carga_horaria' => '20',
            'nivel' => 'Básico',
            'periodo' => '2024-II',
            'status' => 'publicado',
            'precio_umss' => 0,
            'precio_externo' => 200,
            'precio_auxiliar' => 150,
            'instructor_id' => $instructor->id,
        ]);

        Group::create([
            'course_id' => $course->id,
            'nombre' => 'Grupo A',
            'hora_inicio' => '08:00',
            'hora_fin' => '09:30',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
            'status' => 'habilitado',
        ]);
    }
}
