<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use App\Models\EvaluationCriteria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationCriteria>
 */
class EvaluationCriteriaFactory extends Factory
{
    protected $model = EvaluationCriteria::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'nombre' => fake()->randomElement([
                'Examen Parcial',
                'Examen Final',
                'Proyecto Final',
                'Práctica de Laboratorio',
                'Evaluación Continua',
            ]),
            'ponderacion' => 50.00,
        ];
    }
}
