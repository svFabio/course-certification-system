<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Preinscription;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'preinscription_id' => Preinscription::factory(),
            'status' => AttendanceStatus::PRESENTE,
            'lat' => -17.7833000,
            'lng' => -66.1500000,
            'distancia_metros' => 12.50,
        ];
    }

    public function presente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::PRESENTE,
            'distancia_metros' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function ausente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::AUSENTE,
            'lat' => null,
            'lng' => null,
            'distancia_metros' => null,
        ]);
    }

    public function justificado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::JUSTIFICADO,
            'lat' => null,
            'lng' => null,
            'distancia_metros' => null,
        ]);
    }
}
