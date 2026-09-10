<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'preinscription_id' => Preinscription::factory(),
            'monto' => 80.00,
            'metodo' => fake()->randomElement([PaymentMethod::EFECTIVO, PaymentMethod::QR]),
            'numero_comprobante' => 'COMP-' . fake()->unique()->numerify('#####'),
            'verificado_por' => User::factory(),
            'verificado_en' => now(),
        ];
    }

    public function verified(?User $verifier = null): static
    {
        return $this->state(fn (array $attributes) => [
            'verificado_por' => $verifier?->id ?? User::factory(),
            'verificado_en' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verificado_por' => null,
            'verificado_en' => null,
        ]);
    }

    public function qr(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo' => PaymentMethod::QR,
        ]);
    }

    public function efectivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo' => PaymentMethod::EFECTIVO,
        ]);
    }
}
