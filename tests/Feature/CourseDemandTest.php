<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\GroupResource\Pages\ListGroups;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\CourseDemandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('returns a course when every enabled group is at capacity', function () {
    $course = Course::factory()->create(['nombre' => 'Curso Completo Total']);
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 2,
    ]);

    Preinscription::factory()->count(2)->create(['group_id' => $group->id]);

    $result = app(CourseDemandService::class)->coursesAtCapacity();

    expect($result->pluck('id')->all())->toContain($course->id);
    expect($result->pluck('nombre')->all())->toContain('Curso Completo Total');
});

it('excludes courses that still have a free seat in any enabled group', function () {
    $course = Course::factory()->create();
    $fullGroup = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 1,
        'cupo_maximo' => 1,
    ]);
    $groupWithSeat = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 1,
        'cupo_maximo' => 5,
    ]);

    Preinscription::factory()->create(['group_id' => $fullGroup->id]);
    Preinscription::factory()->create(['group_id' => $groupWithSeat->id]);

    $result = app(CourseDemandService::class)->coursesAtCapacity();

    expect($result->pluck('id')->all())->not->toContain($course->id);
});

it('excludes courses with zero enabled groups', function () {
    $courseWithoutEnabledGroups = Course::factory()->create();
    Group::factory()->noHabilitado()->create([
        'course_id' => $courseWithoutEnabledGroups->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $courseWithOnlyClosedGroups = Course::factory()->create();
    Group::factory()->cerrado()->create([
        'course_id' => $courseWithOnlyClosedGroups->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $result = app(CourseDemandService::class)->coursesAtCapacity();

    expect($result->pluck('id')->all())
        ->not->toContain($courseWithoutEnabledGroups->id)
        ->not->toContain($courseWithOnlyClosedGroups->id);
});

it('warns admins on the groups list page when a course has no free seat anywhere', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->create(['nombre' => 'Curso en Sobredemanda']);
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 2,
    ]);

    Preinscription::factory()->count(2)->create(['group_id' => $group->id]);

    session()->forget('filament.notifications');

    Livewire::test(ListGroups::class);

    $notifications = collect(session('filament.notifications', []));
    $demandNotification = $notifications->firstWhere('title', 'Sobredemanda detectada');

    expect($demandNotification)->not->toBeNull();
    expect($demandNotification['body'])->toContain('Curso en Sobredemanda');
    expect($demandNotification['body'])->toContain('Considere abrir un grupo adicional. (HU-14)');
    expect($demandNotification['status'])->toBe('warning');
});

it('shows the occupancy badge on the groups list table', function () {
    $this->actingAs($this->admin);

    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 15,
        'cupo_maximo' => 15,
    ]);

    Preinscription::factory()->count(15)->create(['group_id' => $group->id]);

    Livewire::test(ListGroups::class)->assertSee('15/15');
});
