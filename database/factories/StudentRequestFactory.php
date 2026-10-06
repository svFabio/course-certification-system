<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\StudentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRequest>
 */
class StudentRequestFactory extends Factory
{
    protected $model = StudentRequest::class;

    public function definition(): array
    {
        return [
            'preinscription_id' => Preinscription::factory(),
            'tipo' => StudentRequestType::CAMBIO_GRUPO,
            'group_id_destino' => Group::factory(),
            'motivo' => fake()->sentence(),
            'estado' => StudentRequestStatus::PENDIENTE,
            'motivo_rechazo' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function cambioGrupo(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => StudentRequestType::CAMBIO_GRUPO,
        ]);
    }

    public function devolucion(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => StudentRequestType::DEVOLUCION,
            'group_id_destino' => null,
        ]);
    }

    public function aprobada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => StudentRequestStatus::APROBADA,
            'resolved_at' => now(),
        ]);
    }

    public function rechazada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => StudentRequestStatus::RECHAZADA,
            'motivo_rechazo' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }
}
