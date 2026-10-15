<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Admin\Resources\CourseResource;
use App\Filament\Admin\Resources\CourseResource\RelationManagers\CertificatesRelationManager;
use App\Filament\Admin\Resources\GroupResource\Pages\ViewGroup;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\PreinscriptionsRelationManager;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('configures course_id as default table group in CertificateResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $courseA = Course::factory()->create(['nombre' => 'Arquitectura de Software']);
    $courseB = Course::factory()->create(['nombre' => 'DevOps Avanzado']);

    $certA = Certificate::factory()->create([
        'course_id' => $courseA->id,
        'codigo_unico' => 'CERT-ARQ-001',
    ]);
    $certB = Certificate::factory()->create([
        'course_id' => $courseB->id,
        'codigo_unico' => 'CERT-DEV-002',
    ]);

    Livewire::test(ListCertificates::class)
        ->assertCanSeeTableRecords([$certA, $certB])
        ->assertSee('Arquitectura de Software')
        ->assertSee('DevOps Avanzado');
});

it('displays group name in CertificateResource table', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Especial 1']);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $studentCert = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preinscription->id,
        'tipo' => CertificateType::APROBACION,
    ]);

    $instructor = User::factory()->create();
    $instructorCert = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => null,
        'instructor_id' => $instructor->id,
        'tipo' => CertificateType::INSTRUCTOR,
        'grupos_detalle' => 'Grupo Especial 1, Grupo Especial 2',
    ]);

    Livewire::test(ListCertificates::class)
        ->assertCanSeeTableRecords([$studentCert, $instructorCert])
        ->assertSee('Grupo Especial 1')
        ->assertSee('Grupo Especial 1, Grupo Especial 2');
});

it('registers CertificatesRelationManager in CourseResource', function () {
    expect(CourseResource::getRelations())->toContain(CertificatesRelationManager::class);
});

it('lists only certificates belonging to the owner course in CertificatesRelationManager', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $targetCourse = Course::factory()->create(['nombre' => 'Curso Objetivo']);
    $otherCourse = Course::factory()->create(['nombre' => 'Curso Foráneo']);

    $targetCert = Certificate::factory()->create([
        'course_id' => $targetCourse->id,
        'codigo_unico' => 'TARGET-001',
    ]);

    $otherCert = Certificate::factory()->create([
        'course_id' => $otherCourse->id,
        'codigo_unico' => 'OTHER-002',
    ]);

    Livewire::test(CertificatesRelationManager::class, [
        'ownerRecord' => $targetCourse,
        'pageClass' => CourseResource\Pages\ViewCourse::class,
    ])
        ->assertCanSeeTableRecords([$targetCert])
        ->assertCanNotSeeTableRecords([$otherCert]);
});

it('filters certificates by group in CertificateResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    $groupA = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo A']);
    $groupB = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo B']);

    $preA = Preinscription::factory()->create(['group_id' => $groupA->id]);
    $preB = Preinscription::factory()->create(['group_id' => $groupB->id]);

    $certA = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preA->id,
        'codigo_unico' => 'CERT-GA-001',
    ]);
    $certB = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preB->id,
        'codigo_unico' => 'CERT-GB-002',
    ]);

    Livewire::test(ListCertificates::class)
        ->assertCanSeeTableRecords([$certA, $certB])
        ->filterTable('grupo', $groupA->id)
        ->assertCanSeeTableRecords([$certA])
        ->assertCanNotSeeTableRecords([$certB]);
});

it('filters certificates by group in CertificatesRelationManager', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    $groupA = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Alfa']);
    $groupB = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Beta']);

    $preA = Preinscription::factory()->create(['group_id' => $groupA->id]);
    $preB = Preinscription::factory()->create(['group_id' => $groupB->id]);

    $certA = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preA->id,
        'codigo_unico' => 'REL-ALFA-001',
    ]);
    $certB = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preB->id,
        'codigo_unico' => 'REL-BETA-002',
    ]);

    Livewire::test(CertificatesRelationManager::class, [
        'ownerRecord' => $course,
        'pageClass' => CourseResource\Pages\ViewCourse::class,
    ])
        ->assertCanSeeTableRecords([$certA, $certB])
        ->filterTable('grupo', $groupA->id)
        ->assertCanSeeTableRecords([$certA])
        ->assertCanNotSeeTableRecords([$certB]);
});

it('does not produce N+1 queries when loading certificates in CertificatesRelationManager', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    for ($i = 0; $i < 6; $i++) {
        $pre = Preinscription::factory()->create(['group_id' => $group->id]);
        Certificate::factory()->create([
            'course_id' => $course->id,
            'preinscription_id' => $pre->id,
            'tipo' => CertificateType::APROBACION,
        ]);
    }

    $instructor = User::factory()->create();
    Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => null,
        'instructor_id' => $instructor->id,
        'tipo' => CertificateType::INSTRUCTOR,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(CertificatesRelationManager::class, [
        'ownerRecord' => $course,
        'pageClass' => CourseResource\Pages\ViewCourse::class,
    ]);

    $queryCount = count(DB::getQueryLog());
    expect($queryCount)->toBeLessThanOrEqual(10);
});

it('exposes hierarchical certificate actions and links in course and group views', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create(['nombre' => 'Arquitectura de Datos']);
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Alfa']);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $cert = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preinscription->id,
        'codigo_unico' => 'CERT-HIER-001',
    ]);

    // ViewCourse action exists
    Livewire::test(CourseResource\Pages\ViewCourse::class, ['record' => $course->id])
        ->assertActionExists('imprimirCertificados');

    // GroupsRelationManager has link to group and print action
    Livewire::test(CourseResource\RelationManagers\GroupsRelationManager::class, [
        'ownerRecord' => $course,
        'pageClass' => CourseResource\Pages\ViewCourse::class,
    ])
        ->assertCanSeeTableRecords([$group])
        ->assertTableActionExists('verGrupo')
        ->assertTableActionExists('imprimirCertificados');

    // ViewGroup header action exists
    Livewire::test(ViewGroup::class, ['record' => $group->id])
        ->assertActionExists('imprimirCertificados');

    // PreinscriptionsRelationManager displays certificate and action
    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertCanSeeTableRecords([$preinscription])
        ->assertSee('CERT-HIER-001')
        ->assertTableActionExists('imprimirCertificado');
});

it('downloads course and group certificates as zip archives', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create(['nombre' => 'Curso de Prueba ZIP']);
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo 1']);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preinscription->id,
        'codigo_unico' => 'ZIP-TEST-001',
    ]);

    $responseCourse = $this->get(route('admin.cursos.certificados.zip', $course));
    $responseCourse->assertOk();
    expect($responseCourse->headers->get('content-type'))->toBe('application/zip');

    $responseGroup = $this->get(route('admin.grupos.certificados.zip', $group));
    $responseGroup->assertOk();
    expect($responseGroup->headers->get('content-type'))->toBe('application/zip');
});
