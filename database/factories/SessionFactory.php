<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'fecha' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'dictada' => false,
            'motivo_reprogramacion' => null,
        ];
    }

    public function dictada(): static
    {
        return $this->state(fn (array $attributes) => [
            'dictada' => true,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => now()->addDays(2)->format('Y-m-d'),
            'hora_inicio' => '14:00',
            'hora_fin' => '16:00',
            'dictada' => false,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => now()->subDays(2)->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'dictada' => true,
        ]);
    }
}
