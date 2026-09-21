<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        $holidays = [
            // Feriados Nacionales Bolivia
            ['nombre' => 'Año Nuevo', 'fecha' => "{$year}-01-01", 'alcance' => 'nacional'],
            ['nombre' => 'Día del Estado Plurinacional', 'fecha' => "{$year}-01-22", 'alcance' => 'nacional'],
            ['nombre' => 'Lunes de Carnaval', 'fecha' => "{$year}-02-16", 'alcance' => 'nacional'],
            ['nombre' => 'Martes de Carnaval', 'fecha' => "{$year}-02-17", 'alcance' => 'nacional'],
            ['nombre' => 'Viernes Santo', 'fecha' => "{$year}-04-03", 'alcance' => 'nacional'],
            ['nombre' => 'Día del Trabajo', 'fecha' => "{$year}-05-01", 'alcance' => 'nacional'],
            ['nombre' => 'Corpus Christi', 'fecha' => "{$year}-06-04", 'alcance' => 'nacional'],
            ['nombre' => 'Año Nuevo Andino Amazónico', 'fecha' => "{$year}-06-21", 'alcance' => 'nacional'],
            ['nombre' => 'Día de la Independencia de Bolivia', 'fecha' => "{$year}-08-06", 'alcance' => 'nacional'],
            ['nombre' => 'Día de las Fuerzas Armadas', 'fecha' => "{$year}-08-07", 'alcance' => 'nacional'],
            ['nombre' => 'Día de Todos los Santos', 'fecha' => "{$year}-11-02", 'alcance' => 'nacional'],
            ['nombre' => 'Navidad', 'fecha' => "{$year}-12-25", 'alcance' => 'nacional'],

            // Feriados Departamentales Cochabamba
            ['nombre' => 'Aniversario Cívico de Cochabamba', 'fecha' => "{$year}-09-14", 'alcance' => 'departamental'],
            ['nombre' => 'Fiesta de la Virgen de Urkupiña', 'fecha' => "{$year}-08-14", 'alcance' => 'departamental'],
            ['nombre' => 'Festividad de Urkupiña (Calvario)', 'fecha' => "{$year}-08-15", 'alcance' => 'departamental'],

            // Feriados Institucionales UMSS
            ['nombre' => 'Día de la Autonomía Universitaria', 'fecha' => "{$year}-07-25", 'alcance' => 'universitario'],
            ['nombre' => 'Aniversario de la UMSS', 'fecha' => "{$year}-11-05", 'alcance' => 'universitario'],
            ['nombre' => 'Día del Estudiante / Primavera', 'fecha' => "{$year}-09-21", 'alcance' => 'nacional'],
        ];

        foreach ($holidays as $h) {
            Holiday::updateOrCreate(
                ['fecha' => $h['fecha'], 'alcance' => $h['alcance']],
                ['nombre' => $h['nombre'], 'activo' => true]
            );
        }
    }
}
