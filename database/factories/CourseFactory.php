<?php

declare(strict_types=1);

namespace Database\Factories;

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
        $cargaHoraria = fake()->randomElement(['20', '30']);
        $hours = (int) $cargaHoraria;

        return [
            'nombre' => fake()->sentence(3),
            'contenido' => fake()->paragraphs(2, true),
            'carga_horaria' => $cargaHoraria,
            'nivel' => fake()->randomElement(['Básico', 'Intermedio', 'Avanzado']),
            'periodo' => '2024-' . fake()->randomElement(['I', 'II']),
            'status' => CourseStatus::PUBLICADO,
            'precio_umss' => BusinessRules::calculatePrice($hours, 'umss'),
            'precio_externo' => BusinessRules::calculatePrice($hours, 'externo'),
            'precio_auxiliar' => BusinessRules::calculatePrice($hours, 'auxiliar'),
            'instructor_id' => User::factory(),
        ];
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
        $h = (int) $hours;

        return $this->state(fn (array $attributes) => [
            'carga_horaria' => (string) $h,
            'precio_umss' => BusinessRules::calculatePrice($h, 'umss'),
            'precio_externo' => BusinessRules::calculatePrice($h, 'externo'),
            'precio_auxiliar' => BusinessRules::calculatePrice($h, 'auxiliar'),
        ]);
    }
}
