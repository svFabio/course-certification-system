<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Preinscription;
use App\Models\User;
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
            'instructor_id' => null,
            'course_id' => fn (array $attributes) => Preinscription::find($attributes['preinscription_id'])?->group?->course_id ?? Course::factory(),
            'tipo' => CertificateType::APROBACION,
            'grupos_detalle' => null,
            'codigo_unico' => 'CERT-'.now()->year.'-'.strtoupper(Str::random(8)),
            'pdf_path' => 'certificates/'.fake()->uuid().'.pdf',
            'signature_status' => SignatureStatus::PENDIENTE,
            'emitido_en' => now(),
            'listo_en' => null,
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

    public function instructor(): static
    {
        return $this->state(function (array $attributes) {
            $course = isset($attributes['course_id'])
                ? Course::find($attributes['course_id'])
                : Course::factory()->create();

            return [
                'preinscription_id' => null,
                'instructor_id' => $attributes['instructor_id'] ?? $course?->instructor_id ?? User::factory(),
                'course_id' => $course?->id ?? Course::factory(),
                'tipo' => CertificateType::INSTRUCTOR,
                'grupos_detalle' => $attributes['grupos_detalle'] ?? 'Grupo A',
            ];
        });
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'signature_status' => SignatureStatus::LISTO,
            'listo_en' => now(),
        ]);
    }
}
