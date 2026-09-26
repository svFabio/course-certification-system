<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use App\Support\BusinessRules;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->sentence(3),
            'contenido' => fake()->paragraphs(2, true),
            'carga_horaria' => fake()->randomElement(['20', '30']),
            'nivel' => fake()->randomElement(CourseLevel::values()),
            'periodo' => '2024-'.fake()->randomElement(['I', 'II']),
            'status' => CourseStatus::PUBLICADO,
            'instructor_id' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Course $course): void {
            if ($course->carga_horaria === null) {
                return;
            }

            $prices = BusinessRules::getPricing();
            $defaults = $prices[$course->carga_horaria] ?? null;

            if ($defaults === null) {
                return;
            }

            if ($course->precio_umss === null) {
                $course->precio_umss = $defaults['umss'];
            }

            if ($course->precio_externo === null) {
                $course->precio_externo = $defaults['externo'];
            }

            if ($course->precio_auxiliar === null) {
                $course->precio_auxiliar = $defaults['auxiliar'];
            }
        });
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::PUBLICADO,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::EN_PREPARACION,
        ]);
    }

    public function withHours(string $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'carga_horaria' => (string) $hours,
        ]);
    }
}
