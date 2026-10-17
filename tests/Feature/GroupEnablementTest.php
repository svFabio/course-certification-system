<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-enables a no_habilitado group when pendiente_pago and inscrito seats reach the default cupo_minimo of 15', function () {
    $group = Group::factory()->noHabilitado()->create([
        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
        'cupo_maximo' => 30,
    ]);

    expect(BusinessRules::MIN_GROUP_CAPACITY)->toBe(15)
        ->and($group->cupo_minimo)->toBe(BusinessRules::MIN_GROUP_CAPACITY);

    // 7 pendiente de pago + 7 inscrito = 14 active seats, still below the threshold.
    Preinscription::factory()->count(7)->create(['group_id' => $group->id]);
    Preinscription::factory()->inscrito()->count(7)->create(['group_id' => $group->id]);

    // The 15th ROW is retirado: rows = 15 but active seats = 14, so the group
    // must stay disabled (retirado/rechazado do not count toward the threshold).
    Preinscription::factory()->retirado()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);

    // One more inscrito brings active seats to 15 (rows still include the
    // retirado one), which enables the group.
    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('auto-enables a no_habilitado group at its custom cupo_minimo instead of the default one', function () {
    $group = Group::factory()->noHabilitado()->create([
        'cupo_minimo' => 3,
        'cupo_maximo' => 10,
    ]);

    Preinscription::factory()->count(2)->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);

    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('demotes a habilitado group when a status change drops active seats below cupo_minimo', function () {
    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);
    Preinscription::factory()->create(['group_id' => $group->id]);
    $second = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    $second->update(['status' => PreinscriptionStatus::RETIRADO]);

    expect($second->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO)
        ->and($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);
});

it('does not demote a habilitado group when a preinscription is created below cupo_minimo', function () {
    // The group was enabled while holding fewer active seats than the minimum
    // (e.g. manually enabled); the creation path must never demote it.
    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    Preinscription::factory()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('does not re-evaluate the group when an update changes neither status nor group_id', function () {
    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    // Only 1 active seat < 2: a real evaluation would demote the group, so
    // staying HABILITADO proves the wasChanged(['status', 'group_id']) guard.
    $preinscription->update([
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
        'email' => 'status-unchanged@example.test',
    ]);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    $preinscription->update([
        'group_id' => $group->id,
        'celular' => '71234567',
    ]);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    $preinscription->update(['email' => 'unrelated-change@example.test']);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('re-evaluates the group when a preinscription is deleted', function () {
    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);
    Preinscription::factory()->create(['group_id' => $group->id]);
    $second = Preinscription::factory()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    $second->delete();

    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);
});
