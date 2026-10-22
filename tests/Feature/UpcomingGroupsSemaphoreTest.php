<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Filament\Widgets\UpcomingGroupsWidget;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The capacity source of truth is PENDIENTE_PAGO + INSCRITO (AGENTS.md §2).
 * The dashboard semaphore must use the same rule as the auto-enablement
 * observer, otherwise it can tell the admin to "enable" a group the system
 * already enabled.
 */
beforeEach(function () {
    $this->course = Course::factory()->create(['carga_horaria' => '20']);
});

function semaphoreFor(Group $group): array
{
    $widget = new UpcomingGroupsWidget;

    return [
        'state' => $widget->semaphoreState($group),
        'color' => $widget->semaphoreColor($group),
    ];
}

it('counts pendiente_pago together with inscrito when evaluating the semaphore', function () {
    // 14 inscrito + 1 pendiente_pago = 15 committed seats, exactly cupo_minimo.
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 35,
    ]);

    Preinscription::factory()->count(14)->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    $semaphore = semaphoreFor($group);

    expect($semaphore['state'])->toBe('Habilitado (20 disponibles)')
        ->and($semaphore['color'])->toBe('success');
});

it('does not report a missing seat for a group that is already enabled', function () {
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 35,
    ]);

    Preinscription::factory()->count(14)->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    expect(semaphoreFor($group)['state'])->not->toContain('Faltan');
});

it('reports the full state once committed seats reach cupo_maximo', function () {
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 20,
    ]);

    Preinscription::factory()->count(19)->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    $semaphore = semaphoreFor($group);

    expect($semaphore['state'])->toBe('Cupo Lleno')
        ->and($semaphore['color'])->toBe('danger');
});

it('still reports missing seats below cupo_minimo counted across both statuses', function () {
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->count(3)->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    $semaphore = semaphoreFor($group);

    expect($semaphore['state'])->toBe('Faltan 11 para habilitar')
        ->and($semaphore['color'])->toBe('warning');
});

it('ignores withdrawn and rejected preinscriptions when evaluating the semaphore', function () {
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    Preinscription::factory()->count(2)->retirado()->create(['group_id' => $group->id]);
    Preinscription::factory()->count(4)->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    // No committed seats: 15 missing, not 9.
    expect(semaphoreFor($group)['state'])->toBe('Faltan 15 para habilitar');
});

it('matches the group enabled state produced by the auto-enablement observer', function () {
    $group = Group::factory()->create([
        'course_id' => $this->course->id,
        'status' => GroupStatus::NO_HABILITADO,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    // 7 pendiente_pago + 8 inscrito = 15 committed seats → observer enables it.
    Preinscription::factory()->count(7)->pendiente()->create(['group_id' => $group->id]);
    Preinscription::factory()->count(8)->inscrito()->create(['group_id' => $group->id]);

    $group->refresh();

    expect($group->status)->toBe(GroupStatus::HABILITADO);

    $group->loadCount([
        'preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO),
        'preinscriptions as pending_count' => fn ($q) => $q->where('status', PreinscriptionStatus::PENDIENTE_PAGO),
    ]);

    // The semaphore must agree with the state the observer already applied.
    expect(semaphoreFor($group)['state'])->not->toContain('Faltan')
        ->and(semaphoreFor($group)['color'])->toBe('success');
});
