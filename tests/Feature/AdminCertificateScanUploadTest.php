<?php

declare(strict_types=1);

use App\Enums\CertificateLifecycleStatus;
use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\CourseResource\Pages\ViewCourse;
use App\Filament\Admin\Resources\CourseResource\RelationManagers\CertificatesRelationManager;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\CertificateService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('cloudinary');
    Storage::fake('local');
});

it('marks a generated student certificate as delivered', function () {
    $certificate = Certificate::factory()->create([
        'tipo' => CertificateType::APROBACION,
        'estado' => CertificateLifecycleStatus::GENERADO,
    ]);

    $updated = app(CertificateService::class)->markAsDelivered($certificate);

    expect($updated->estado)->toBe(CertificateLifecycleStatus::ENTREGADO)
        ->and($updated->entregado_en)->not->toBeNull();
});

it('attaches a scanned pdf and marks the certificate as digitized', function () {
    $certificate = Certificate::factory()->create([
        'tipo' => CertificateType::APROBACION,
        'codigo_unico' => 'CERT-2026-SCAN01',
        'estado' => CertificateLifecycleStatus::ENTREGADO,
    ]);

    $file = UploadedFile::fake()->create('firmado.pdf', 200, 'application/pdf');

    $updated = app(CertificateService::class)->attachScannedCopy($certificate, $file);

    expect($updated->estado)->toBe(CertificateLifecycleStatus::DIGITALIZADO)
        ->and($updated->hasScannedCopy())->toBeTrue()
        ->and($updated->escaneado_en)->not->toBeNull()
        ->and($updated->pdf_escaneado_path)->toBe('certificates/scanned/CERT-2026-SCAN01.pdf');

    expect(
        Storage::disk('cloudinary')->exists($updated->pdf_escaneado_path)
        || Storage::disk('local')->exists($updated->pdf_escaneado_path)
    )->toBeTrue();
});

it('attaches a scanned copy from an existing storage path string', function () {
    $path = 'certificates/scanned/CERT-PATH.pdf';
    Storage::disk('cloudinary')->put($path, 'fake-pdf-content');

    $certificate = Certificate::factory()->create([
        'tipo' => CertificateType::APROBACION,
        'estado' => CertificateLifecycleStatus::GENERADO,
    ]);

    $updated = app(CertificateService::class)->attachScannedCopy($certificate, $path);

    expect($updated->pdf_escaneado_path)->toBe($path)
        ->and($updated->estado)->toBe(CertificateLifecycleStatus::DIGITALIZADO);
});

it('rejects scanned upload for instructor certificates', function () {
    $certificate = Certificate::factory()->instructor()->create();

    app(CertificateService::class)->attachScannedCopy(
        $certificate,
        UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')
    );
})->throws(ValidationException::class);

it('downloads the digitized certificate file for admin', function () {
    $path = 'certificates/scanned/CERT-DL.pdf';
    Storage::disk('cloudinary')->put($path, '%PDF-1.4 fake');

    $certificate = Certificate::factory()->digitalizado()->create([
        'pdf_escaneado_path' => $path,
    ]);

    $response = app(CertificateService::class)->downloadScanned($certificate);

    expect($response->getStatusCode())->toBe(200);
});

it('exposes admin actions to upload mark delivered and download digital on relation manager', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $generado = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preinscription->id,
        'tipo' => CertificateType::APROBACION,
        'estado' => CertificateLifecycleStatus::GENERADO,
        'codigo_unico' => 'CERT-UI-GEN',
    ]);

    $digitalizado = Certificate::factory()->digitalizado()->create([
        'course_id' => $course->id,
        'preinscription_id' => Preinscription::factory()->inscrito()->create(['group_id' => $group->id])->id,
        'tipo' => CertificateType::APROBACION,
        'codigo_unico' => 'CERT-UI-DIG',
    ]);

    Livewire::test(CertificatesRelationManager::class, [
        'ownerRecord' => $course,
        'pageClass' => ViewCourse::class,
    ])
        ->assertCanSeeTableRecords([$generado, $digitalizado])
        ->assertSee('Generado')
        ->assertSee('Digitalizado')
        ->assertTableActionExists('subirEscaneo', record: $generado)
        ->assertTableActionExists('marcarEntregado', record: $generado)
        ->assertTableActionHidden('descargarDigital', record: $generado)
        ->assertTableActionExists('descargarDigital', record: $digitalizado)
        ->assertTableActionHidden('marcarEntregado', record: $digitalizado);
});
