<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Preinscription>
 */
class PreinscriptionFactory extends Factory
{
    protected $model = Preinscription::class;

    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'ci' => (string) fake()->unique()->numberBetween(1000000, 99999999),
            'nombres' => fake()->firstName(),
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => fake()->lastName(),
            'celular' => '7'.fake()->numerify('#######'),
            'email' => fake()->safeEmail(),
            'tipo_participante' => TipoParticipante::UMSS->value,
            'cod_sis' => fake()->boolean(60) ? fake()->numerify('202######') : null,
            'status' => PreinscriptionStatus::PENDIENTE_PAGO,
            'fotocopia_ci' => false,
        ];
    }

    public function inscrito(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreinscriptionStatus::INSCRITO,
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreinscriptionStatus::PENDIENTE_PAGO,
        ]);
    }

    public function retirado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreinscriptionStatus::RETIRADO,
        ]);
    }

    public function umss(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_participante' => TipoParticipante::UMSS->value,
        ]);
    }

    public function externo(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_participante' => TipoParticipante::EXTERNO->value,
        ]);
    }

    public function auxiliar(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_participante' => TipoParticipante::AUXILIAR->value,
        ]);
    }

    public function auxiliarWithApprovedCertificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_participante' => TipoParticipante::AUXILIAR->value,
            'auxiliar_certificado_path' => 'auxiliar-certificados/test-certificado.pdf',
            'auxiliar_certificado_aprobado' => true,
            'auxiliar_certificado_aprobado_en' => now(),
        ]);
    }
}
