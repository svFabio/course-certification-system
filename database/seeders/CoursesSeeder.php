<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
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
            'status' => 'publicado',
            'fecha_inicio_preinscripcion' => now()->toDateString(),
            'fecha_fin_preinscripcion' => now()->addDays(30)->toDateString(),
            'precio_umss' => BusinessRules::calculatePrice(20, 'umss'),
            'precio_externo' => BusinessRules::calculatePrice(20, 'externo'),
            'precio_auxiliar' => BusinessRules::calculatePrice(20, 'auxiliar'),
            'instructor_id' => $instructor->id,
        ]);

        $groupA = Group::create([
            'course_id' => $course->id,
            'nombre' => 'Grupo A',
            'hora_inicio' => '08:00',
            'hora_fin' => '09:30',
            'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
            'cupo_maximo' => 30,
            'status' => 'habilitado',
        ]);

        Preinscription::create([
            'group_id' => $groupA->id,
            'ci' => '1234567',
            'nombres' => 'Estudiante',
            'apellido_paterno' => 'Demo',
            'apellido_materno' => 'UMSS',
            'celular' => '70000000',
            'email' => 'student@umss.edu.bo',
            'tipo_participante' => 'umss',
            'status' => 'pendiente_pago',
        ]);

        $draft = Course::create([
            'nombre' => 'Curso en preparación (demo HU-06)',
            'contenido' => 'Curso de prueba para publicar y cerrar preinscripción.',
            'carga_horaria' => '20',
            'nivel' => 'Básico',
            'periodo' => '2024-II',
            'status' => 'en_preparacion',
            'precio_umss' => BusinessRules::calculatePrice(20, 'umss'),
            'precio_externo' => BusinessRules::calculatePrice(20, 'externo'),
            'precio_auxiliar' => BusinessRules::calculatePrice(20, 'auxiliar'),
            'instructor_id' => $instructor->id,
        ]);

        Group::create([
            'course_id' => $draft->id,
            'nombre' => 'Grupo B',
            'hora_inicio' => '18:00',
            'hora_fin' => '19:30',
            'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
            'cupo_maximo' => 25,
            'status' => 'habilitado',
        ]);
    }
}
