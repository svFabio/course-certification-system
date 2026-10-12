<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\CertificateService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a student download a ready certificate pdf and stores it in cloudinary', function () {
    $student = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $student->assignRole(UserRole::STUDENT->value);

    $instructor = User::factory()->create();
    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-2026-TESTHU31',
        'emitido_en' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('certificado.descargar', $certificate))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($certificate->fresh()->pdf_path)->toBe('certificates/CERT-2026-TESTHU31.pdf');
    Storage::disk('cloudinary')->assertExists('certificates/CERT-2026-TESTHU31.pdf');
});

it('builds a public verification url for sharing', function () {
    $certificate = Certificate::factory()->create([
        'codigo_unico' => 'CERT-2026-SHARE01',
        'signature_status' => SignatureStatus::LISTO,
    ]);

    $url = app(CertificateService::class)->verificationUrl($certificate);

    expect($url)->toContain('verificar-certificado')
        ->and($url)->toContain('CERT-2026-SHARE01');
});

it('rejects download when certificate is not ready', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'signature_status' => SignatureStatus::PENDIENTE,
    ]);

    $this->actingAs($student)
        ->get(route('certificado.descargar', $certificate))
        ->assertSessionHasErrors();
});

it('downloads pre-stored certificate directly from storage', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $path = 'certificates/CERT-PRESTORED.pdf';
    Storage::disk('cloudinary')->put($path, '%PDF-1.4 Fake PDF Content', ['resource_type' => 'raw']);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-PRESTORED',
        'pdf_path' => $path,
        'emitido_en' => now(),
    ]);

    $response = $this->actingAs($student)
        ->get(route('certificado.descargar', $certificate));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('falls back to on-the-fly generation when storage stream encounters an error', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-2026-STREAMERR',
        'pdf_path' => 'certificates/CERT-2026-STREAMERR.pdf',
        'emitido_en' => now(),
    ]);

    Storage::disk('cloudinary')->put('certificates/CERT-2026-STREAMERR.pdf', 'dummy');

    $diskMock = Mockery::mock(Storage::disk('cloudinary'))->makePartial();
    $diskMock->shouldReceive('exists')->andReturn(true);
    $diskMock->shouldReceive('download')->andThrow(new RuntimeException('Storage stream failed'));
    Storage::set('cloudinary', $diskMock);

    $response = $this->actingAs($student)
        ->get(route('certificado.descargar', $certificate));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
