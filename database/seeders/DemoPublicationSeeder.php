<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoPublicationSeeder extends Seeder
{
    public function run(): void
    {
        Course::query()->update([
            'fecha_inicio_preinscripcion' => now()->toDateString(),
            'fecha_fin_preinscripcion' => now()->addDays(30)->toDateString(),
        ]);

        if (Course::where('nombre', 'Curso en preparación (demo HU-06)')->exists()) {
            return;
        }

        $instructor = User::where('email', 'instructor@umss.edu.bo')->first();

        if (! $instructor) {
            return;
        }

        $draft = Course::create([
            'nombre' => 'Curso en preparación (demo HU-06)',
            'contenido' => 'Curso de prueba para publicar y cerrar preinscripción.',
            'carga_horaria' => '20',
            'nivel' => 'Básico',
            'periodo' => '2024-II',
            'status' => 'en_preparacion',
            'precio_umss' => 80,
            'precio_externo' => 100,
            'precio_auxiliar' => 40,
            'instructor_id' => $instructor->id,
        ]);

        Group::create([
            'course_id' => $draft->id,
            'nombre' => 'Grupo B',
            'hora_inicio' => '18:00',
            'hora_fin' => '19:30',
            'cupo_minimo' => 15,
            'cupo_maximo' => 25,
            'status' => 'habilitado',
        ]);
    }
}
