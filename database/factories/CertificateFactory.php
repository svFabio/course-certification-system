<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'preinscription_id' => Preinscription::factory(),
            'course_id' => Course::factory(),
            'tipo' => CertificateType::APROBACION,
            'codigo_unico' => 'CERT-'.strtoupper(Str::random(8)),
            'pdf_path' => 'certificates/'.fake()->uuid().'.pdf',
            'signature_status' => SignatureStatus::PENDIENTE,
            'emitido_en' => now(),
        ];
    }

    public function aprobacion(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => CertificateType::APROBACION,
        ]);
    }

    public function asistencia(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => CertificateType::ASISTENCIA,
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_status' => SignatureStatus::LISTO,
        ]);
    }
}
