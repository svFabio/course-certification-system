<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Filament\Admin\Resources\GroupResource\Pages\CreateGroup;
use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use App\Support\BusinessRules;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
});

it('persists hora_fin computed from hora_inicio and carga_horaria when creating a group through GroupResource', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $course = Course::factory()->withHours('20')->create();

    Livewire::test(CreateGroup::class)
        ->fillForm([
            'course_id' => $course->id,
            'nombre' => 'Grupo Persistencia Hora Fin',
            'aula' => 'Laboratorio 1',
            'hora_inicio' => '09:45',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
            'status' => GroupStatus::HABILITADO->value,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $group = Group::query()->where('nombre', 'Grupo Persistencia Hora Fin')->first();

    expect($group)->not->toBeNull();
    expect($group->hora_fin)->not->toBeNull();

    $expected = Carbon::parse('09:45')
        ->addMinutes((int) (BusinessRules::SESSION_DURATION_HOURS['20'] * 60))
        ->format('H:i');

    expect($group->hora_fin->format('H:i'))->toBe($expected);
    expect($group->hora_fin->format('H:i'))->toBe('11:15');
});

it('computes hora_fin on the Group saving event when the attribute is not provided', function () {
    $course = Course::factory()->withHours('30')->create();

    $group = Group::create([
        'course_id' => $course->id,
        'nombre' => 'Grupo Sin Hora Fin',
        'aula' => 'Laboratorio 2',
        'hora_inicio' => '09:45',
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
        'status' => GroupStatus::HABILITADO,
    ]);

    expect($group->hora_fin)->not->toBeNull();
    expect($group->hora_fin->format('H:i'))->toBe('12:15');
});
