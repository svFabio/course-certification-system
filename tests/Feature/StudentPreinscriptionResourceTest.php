<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentPreinscriptionResource;
use App\Filament\Student\Resources\StudentPreinscriptionResource\Pages\ListStudentPreinscriptions;
use App\Filament\Student\Resources\StudentPreinscriptionResource\Pages\ViewStudentPreinscription;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('student'));
});

it('lists only preinscriptions belonging to the authenticated student', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $ownPreinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $otherPreinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
    ]);

    $this->actingAs($student);

    Livewire::test(ListStudentPreinscriptions::class)
        ->assertCanSeeTableRecords([$ownPreinscription])
        ->assertCanNotSeeTableRecords([$otherPreinscription]);
});

it('returns 404 when a student attempts to view another student preinscription', function () {
    $student = User::factory()->create();
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $otherPreinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
    ]);

    $this->actingAs($student);

    $this->get(StudentPreinscriptionResource::getUrl('view', ['record' => $otherPreinscription]))
        ->assertNotFound();

    expect(fn () => Livewire::test(ViewStudentPreinscription::class, ['record' => $otherPreinscription->getKey()]))
        ->toThrow(ModelNotFoundException::class);
});
