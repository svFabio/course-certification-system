<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InterestStatus;
use App\Models\Course;
use App\Models\CourseInterest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseInterest>
 */
class CourseInterestFactory extends Factory
{
    protected $model = CourseInterest::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'email' => fake()->unique()->safeEmail(),
            'user_id' => null,
            'estado' => InterestStatus::INTERESADO,
            'notificado_at' => null,
            'preinscription_id' => null,
            'notas' => null,
        ];
    }

    public function notificado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => InterestStatus::NOTIFICADO,
            'notificado_at' => now(),
        ]);
    }

    public function convertido(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => InterestStatus::CONVERTIDO,
        ]);
    }

    public function descartado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => InterestStatus::DESCARTADO,
        ]);
    }
}
