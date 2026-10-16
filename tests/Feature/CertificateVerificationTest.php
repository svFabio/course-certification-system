<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Livewire\CertificateVerificationComponent;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Livewire\Livewire;

it('shows valid certificate details for an emitted student certificate via public url', function () {
    $course = Course::factory()->create(['carga_horaria' => '20', 'nombre' => 'Curso Verificacion']);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $pre = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $pre->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'codigo_unico' => 'CERT-2026-VALID01',
    ]);

    Livewire::test(CertificateVerificationComponent::class, ['codigo' => $certificate->codigo_unico])
        ->assertSet('searched', true)
        ->assertSet('certificate.nombre', $certificate->beneficiaryName())
        ->assertSet('certificate.valido', true)
        ->assertSet('certificate.estado_validez', 'Certificado válido')
        ->assertSee('Certificado válido')
        ->assertSee('Curso Verificacion')
        ->assertDontSee($pre->ci ?? 'SENSITIVE_SHOULD_NOT_APPEAR');
});

it('shows not found message for unknown codes', function () {
    Livewire::test(CertificateVerificationComponent::class)
        ->set('codigo', 'CERT-2026-MISSING')
        ->call('verify')
        ->assertSet('certificate', null)
        ->assertSee('Certificado no encontrado. Verifique el código ingresado');
});

it('verifies instructor certificates by public code', function () {
    $instructor = User::factory()->create(['name' => 'Docente Demo']);
    $course = Course::factory()->create([
        'instructor_id' => $instructor->id,
        'nombre' => 'Curso Instructor',
    ]);

    Certificate::factory()->instructor()->create([
        'instructor_id' => $instructor->id,
        'course_id' => $course->id,
        'grupos_detalle' => 'Grupo A, Grupo B',
        'codigo_unico' => 'CERT-2026-INST01',
    ]);

    Livewire::test(CertificateVerificationComponent::class, ['codigo' => 'CERT-2026-INST01'])
        ->assertSet('certificate.rol', 'Instructor')
        ->assertSet('certificate.nombre', 'Docente Demo')
        ->assertSee('Grupo A, Grupo B')
        ->assertSee('Certificado válido');
});
