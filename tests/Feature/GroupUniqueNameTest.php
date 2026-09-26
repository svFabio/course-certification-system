<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Admin\Resources\CourseResource\Pages\EditCourse;
use App\Filament\Admin\Resources\CourseResource\RelationManagers\GroupsRelationManager;
use App\Filament\Admin\Resources\GroupResource\Pages\CreateGroup;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
});

it('prevents creating two groups with the same name under the same course via GroupResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    Group::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Grupo 1',
    ]);

    Livewire::test(CreateGroup::class)
        ->fillForm([
            'course_id' => $course->id,
            'nombre' => 'Grupo 1',
            'hora_inicio' => '08:15',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
        ])
        ->call('create')
        ->assertHasFormErrors(['nombre' => 'unique']);
});

it('allows creating groups with the same name under different courses via GroupResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();

    Group::factory()->create([
        'course_id' => $courseA->id,
        'nombre' => 'Grupo 1',
    ]);

    Livewire::test(CreateGroup::class)
        ->fillForm([
            'course_id' => $courseB->id,
            'nombre' => 'Grupo 1',
            'hora_inicio' => '08:15',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Group::where('nombre', 'Grupo 1')->count())->toBe(2);
});

it('prevents creating duplicate group name under the same course in GroupsRelationManager', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create();
    Group::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Grupo 1',
    ]);

    Livewire::test(GroupsRelationManager::class, [
        'ownerRecord' => $course,
        'pageClass' => EditCourse::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'nombre' => 'Grupo 1',
            'hora_inicio' => '08:15',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
        ])
        ->callMountedTableAction()
        ->assertHasTableActionErrors(['nombre' => 'unique']);
});

it('enforces database level unique constraint for course_id and nombre', function () {
    $course = Course::factory()->create();

    Group::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Grupo 1',
    ]);

    expect(function () use ($course) {
        Group::factory()->create([
            'course_id' => $course->id,
            'nombre' => 'Grupo 1',
        ]);
    })->toThrow(UniqueConstraintViolationException::class);
});
