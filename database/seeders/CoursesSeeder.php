<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use App\Support\BusinessRules;
use Illuminate\Database\Seeder;

class CoursesSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@umss.edu.bo')->first();

        $course = Course::create([
            'nombre' => 'Introducción a la Programación Web',
            'contenido' => 'Curso introductorio a HTML, CSS, JavaScript y PHP.',
            'carga_horaria' => '20',
            'nivel' => 'Básico',
            'periodo' => '2024-II',
            'status' => CourseStatus::PUBLICADO,
            'precio_umss' => BusinessRules::calculatePrice(20, 'umss'),
            'precio_externo' => BusinessRules::calculatePrice(20, 'externo'),
            'precio_auxiliar' => BusinessRules::calculatePrice(20, 'auxiliar'),
            'instructor_id' => $instructor->id,
        ]);

        Group::create([
            'course_id' => $course->id,
            'nombre' => 'Grupo A',
            'hora_inicio' => '08:00',
            'hora_fin' => '09:30',
            'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
            'cupo_maximo' => 30,
            'status' => GroupStatus::HABILITADO,
        ]);
    }
}
