<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->course = Course::factory()->create(['carga_horaria' => '20']);
    $this->group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    // 2 PENDIENTE_PAGO + 1 INSCRITO count as occupied; RECHAZADO/RETIRADO do not.
    Preinscription::factory()->count(2)->pendiente()->create(['group_id' => $this->group->id]);
    Preinscription::factory()->inscrito()->create(['group_id' => $this->group->id]);
    Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);
    Preinscription::factory()->retirado()->create(['group_id' => $this->group->id]);
});

it('keeps cupos_ocupados identical across confirmed_count, loaded relation, and fallback query', function () {
    $withCount = Group::withConfirmedCount()->findOrFail($this->group->id);
    $withRelation = Group::with('preinscriptions')->findOrFail($this->group->id);
    $plain = Group::findOrFail($this->group->id);

    expect($withCount->cupos_ocupados)->toBe(3)
        ->and($withRelation->cupos_ocupados)->toBe(3)
        ->and($plain->cupos_ocupados)->toBe(3)
        ->and($withCount->cupos_disponibles)->toBe(27)
        ->and($withRelation->cupos_disponibles)->toBe(27)
        ->and($plain->cupos_disponibles)->toBe(27)
        ->and($withCount->capacity_percentage)->toBe(10.0)
        ->and($withRelation->capacity_percentage)->toBe(10.0)
        ->and($plain->capacity_percentage)->toBe(10.0)
        ->and($withRelation->estaLleno)->toBeFalse();
});

it('never issues per-row count queries while iterating groups with eager-loaded preinscriptions', function () {
    $otherCourse = Course::factory()->create(['carga_horaria' => '30']);
    $otherGroup = Group::factory()->create([
        'course_id' => $otherCourse->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    Preinscription::factory()->count(2)->inscrito()->create(['group_id' => $otherGroup->id]);

    $groups = Group::with('preinscriptions')->orderBy('id')->get();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $snapshot = $groups->map(fn (Group $group): array => [
        'ocupados' => $group->cupos_ocupados,
        'disponibles' => $group->cupos_disponibles,
        'porcentaje' => $group->capacity_percentage,
        'lleno' => $group->estaLleno,
    ])->all();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and($snapshot[0]['ocupados'])->toBe(3)
        ->and($snapshot[1]['ocupados'])->toBe(2)
        ->and($snapshot[1]['disponibles'])->toBe(28)
        ->and($snapshot[1]['lleno'])->toBeFalse();
});

it('prefers eager-loaded confirmed_count over a constrained loaded relation', function () {
    $group = Group::withCount(['preinscriptions as confirmed_count' => fn ($q) => $q->whereIn('status', [
        PreinscriptionStatus::PENDIENTE_PAGO,
        PreinscriptionStatus::INSCRITO,
    ])])
        ->with(['preinscriptions' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO)])
        ->findOrFail($this->group->id);

    expect($group->cupos_ocupados)->toBe(3);
});
