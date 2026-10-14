<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Pages\Settings\CertificateSignatureSettings;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CertificateService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('cloudinary');
});

it('downloads scanned pdf when certificate has es_escaneado true', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $scannedPath = 'certificates/scanned/cert-fisico-test.pdf';
    Storage::disk('cloudinary')->put($scannedPath, '%PDF-1.4 Scanned Test Content');

    $course = Course::factory()->create();
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
        'codigo_unico' => 'CERT-2026-SCAN01',
        'pdf_path' => $scannedPath,
        'es_escaneado' => true,
        'emitido_en' => now(),
    ]);

    $response = $this->actingAs($student)
        ->get(route('certificado.descargar', $certificate));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($certificate->fresh()->es_escaneado)->toBeTrue();
});

it('embeds authority signatures from system settings and instructor profile into generated pdf', function () {
    $directorPng = UploadedFile::fake()->image('director_sig.png', 400, 200);
    $jefePng = UploadedFile::fake()->image('jefe_sig.png', 400, 200);
    $docentePng = UploadedFile::fake()->image('docente_sig.png', 400, 200);

    $dirPath = 'firmas/autoridades/director.png';
    $jefePath = 'firmas/autoridades/jefe.png';
    $docentePath = 'firmas/docentes/instructor.png';

    Storage::disk('cloudinary')->put($dirPath, $directorPng->getContent());
    Storage::disk('cloudinary')->put($jefePath, $jefePng->getContent());
    Storage::disk('cloudinary')->put($docentePath, $docentePng->getContent());

    SystemSetting::set('director_academico_nombre', 'Ing. Director Test');
    SystemSetting::set('director_academico_cargo', 'Director Académico FC');
    SystemSetting::set('director_academico_firma_path', $dirPath);

    SystemSetting::set('jefe_departamento_nombre', 'Lic. Jefe Test');
    SystemSetting::set('jefe_departamento_cargo', 'Jefe de Departamento FC');
    SystemSetting::set('jefe_departamento_firma_path', $jefePath);

    $instructor = User::factory()->create([
        'name' => 'Prof. Instructor Test',
        'firma_digital_path' => $docentePath,
    ]);
    $instructor->assignRole(UserRole::INSTRUCTOR->value);

    $course = Course::factory()->create(['instructor_id' => $instructor->id]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-2026-STAMP01',
        'emitido_en' => now(),
    ]);

    $pdf = app(CertificateService::class)->generatePdf($certificate);
    $content = $pdf->output();

    expect($content)->not->toBeEmpty()
        ->and(strlen($content))->toBeGreaterThan(1000);
});

it('allows admin to save institutional authority signatures via settings page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);

    $this->actingAs($admin);

    $directorName = fake()->name();
    $jefeName = fake()->name();

    Livewire::test(CertificateSignatureSettings::class)
        ->fillForm([
            'director_academico_nombre' => $directorName,
            'director_academico_cargo' => 'Director Académico - FC',
            'jefe_departamento_nombre' => $jefeName,
            'jefe_departamento_cargo' => 'Jefe de Departamento - FC',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SystemSetting::get('director_academico_nombre'))->toBe($directorName)
        ->and(SystemSetting::get('jefe_departamento_nombre'))->toBe($jefeName);
});

it('allows admin to upload scanned pdf and restore digital version via certificate resource actions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $certificate = Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-2026-OVERRIDE',
        'es_escaneado' => false,
        'pdf_path' => 'certificates/CERT-2026-OVERRIDE.pdf',
        'emitido_en' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListCertificates::class)
        ->assertTableActionVisible('subirPdfEscaneado', $certificate)
        ->callTableAction('subirPdfEscaneado', $certificate, [
            'pdf_escaneado_path' => ['certificates/scanned/manual-scan.pdf'],
        ])
        ->assertHasNoTableActionErrors();

    $certificate->refresh();
    expect($certificate->es_escaneado)->toBeTrue()
        ->and($certificate->pdf_path)->toBe('certificates/scanned/manual-scan.pdf');

    Livewire::test(ListCertificates::class)
        ->assertTableActionVisible('restaurarPdfDigital', $certificate)
        ->callTableAction('restaurarPdfDigital', $certificate)
        ->assertHasNoTableActionErrors();

    $certificate->refresh();
    expect($certificate->es_escaneado)->toBeFalse()
        ->and($certificate->pdf_path)->toBe('certificates/CERT-2026-OVERRIDE.pdf');
});
