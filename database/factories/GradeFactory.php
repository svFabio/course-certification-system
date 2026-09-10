<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'evaluation_criteria_id' => EvaluationCriteria::factory(),
            'preinscription_id' => Preinscription::factory(),
            'nota' => fake()->randomFloat(2, 50, 100),
        ];
    }

    public function passing(): static
    {
        return $this->state(fn (array $attributes) => [
            'nota' => fake()->randomFloat(2, 70, 100),
        ]);
    }

    public function failing(): static
    {
        return $this->state(fn (array $attributes) => [
            'nota' => fake()->randomFloat(2, 0, 69.99),
        ]);
    }
}
