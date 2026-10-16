<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Admin\Resources\CertificateResource\Pages\ViewCertificate;
use App\Filament\Admin\Resources\CourseResource\RelationManagers\GroupsRelationManager;
use App\Filament\Admin\Resources\GroupResource\Pages\ViewGroup;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\PreinscriptionsRelationManager;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists courses in CertificateResource without accordions or grouping dropdowns', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $courseA = Course::factory()->create(['nombre' => 'Arquitectura de Software']);
    $courseB = Course::factory()->create(['nombre' => 'DevOps Avanzado']);

    Certificate::factory()->create([
        'course_id' => $courseA->id,
        'codigo_unico' => 'CERT-ARQ-001',
    ]);
    Certificate::factory()->create([
        'course_id' => $courseB->id,
        'codigo_unico' => 'CERT-DEV-002',
    ]);

    Livewire::test(ListCertificates::class)
        ->assertCanSeeTableRecords([$courseA, $courseB])
        ->assertSee('Arquitectura de Software')
        ->assertSee('DevOps Avanzado')
        ->assertTableActionExists('verGrupos')
        ->assertTableActionDoesNotExist('imprimirCertificados');
});

it('shows only groups of that course when viewing a course in CertificateResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $targetCourse = Course::factory()->create(['nombre' => 'Curso Objetivo']);
    $otherCourse = Course::factory()->create(['nombre' => 'Curso Foráneo']);

    $ownGroup1 = Group::factory()->create(['course_id' => $targetCourse->id, 'nombre' => 'Grupo Propio 1']);
    $ownGroup2 = Group::factory()->create(['course_id' => $targetCourse->id, 'nombre' => 'Grupo Propio 2']);
    $otherGroup = Group::factory()->create(['course_id' => $otherCourse->id, 'nombre' => 'Grupo Foráneo']);

    Certificate::factory()->create([
        'course_id' => $targetCourse->id,
        'codigo_unico' => 'TARGET-CERT-01',
    ]);

    // ViewCertificate shows course info and single print button for course certificates
    Livewire::test(ViewCertificate::class, ['record' => $targetCourse->id])
        ->assertSee('Curso Objetivo')
        ->assertActionExists('imprimirCertificados');

    // GroupsRelationManager within this course shows ONLY its groups and navigates to group view
    Livewire::test(GroupsRelationManager::class, [
        'ownerRecord' => $targetCourse,
        'pageClass' => ViewCertificate::class,
    ])
        ->assertCanSeeTableRecords([$ownGroup1, $ownGroup2])
        ->assertCanNotSeeTableRecords([$otherGroup])
        ->assertSee('Grupo Propio 1')
        ->assertSee('Grupo Propio 2')
        ->assertDontSee('Grupo Foráneo')
        ->assertTableActionExists('verGrupo')
        ->assertTableActionDoesNotExist('imprimirCertificados');
});

it('navigates from group view to students list showing individual certificates', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create(['nombre' => 'Curso con Certificados']);
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo 1']);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    $cert = Certificate::factory()->create([
        'course_id' => $course->id,
        'preinscription_id' => $preinscription->id,
        'codigo_unico' => 'CERT-STUDENT-001',
        'tipo' => CertificateType::APROBACION,
    ]);

    // ViewGroup has the group print action
    Livewire::test(ViewGroup::class, ['record' => $group->id])
        ->assertActionExists('imprimirCertificados');

    // PreinscriptionsRelationManager shows student certificate and action
    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertCanSeeTableRecords([$preinscription])
        ->assertSee('CERT-STUDENT-001')
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
