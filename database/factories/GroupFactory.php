<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GroupStatus;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'nombre' => 'Grupo ' . fake()->unique()->numberBetween(1, 999),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
            'status' => GroupStatus::HABILITADO,
        ];
    }

    public function habilitado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupStatus::HABILITADO,
        ]);
    }

    public function noHabilitado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupStatus::NO_HABILITADO,
        ]);
    }

    public function cerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupStatus::CERRADO,
        ]);
    }
}
