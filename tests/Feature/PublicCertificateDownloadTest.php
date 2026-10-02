<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Livewire\CertificateVerificationComponent;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('cloudinary');
});

it('allows unauthenticated public user to download a ready signed certificate by unique code', function () {
    $instructor = User::factory()->create();
    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => fake()->safeEmail(),
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $codigo = 'CERT-2026-'.fake()->unique()->bothify('????####');

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => $codigo,
        'emitido_en' => now(),
    ]);

    $response = $this->get(route('certificado.publico.descargar', ['codigo' => $codigo]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rejects public download with 404 when certificate signatures are pending', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
    ]);

    $codigo = 'CERT-2026-PENDING'.fake()->numerify('###');

    Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'signature_status' => SignatureStatus::PENDIENTE,
        'codigo_unico' => $codigo,
    ]);

    $response = $this->get(route('certificado.publico.descargar', ['codigo' => $codigo]));

    $response->assertNotFound();
});

it('rejects public download with 404 for unknown unique code', function () {
    $response = $this->get(route('certificado.publico.descargar', ['codigo' => 'CERT-INEXISTENTE-999']));

    $response->assertNotFound();
});

it('renders official pdf download button in verification view when certificate is ready', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
    ]);

    $codigo = 'CERT-2026-VERIFY'.fake()->numerify('###');

    Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => $codigo,
        'emitido_en' => now(),
    ]);

    Livewire::test(CertificateVerificationComponent::class, ['codigo' => $codigo])
        ->assertSee('Certificado válido')
        ->assertSee('Descargar Certificado PDF')
        ->assertSee(route('certificado.publico.descargar', ['codigo' => $codigo]));
});

it('does not render download button in verification view when certificate is still pending signatures', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
    ]);

    $codigo = 'CERT-2026-PENDING'.fake()->numerify('###');

    Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'signature_status' => SignatureStatus::PENDIENTE,
        'codigo_unico' => $codigo,
    ]);

    Livewire::test(CertificateVerificationComponent::class, ['codigo' => $codigo])
        ->assertSee('Registrado — pendiente de firmas')
        ->assertDontSee('Descargar Certificado PDF');
});
