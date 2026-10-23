<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\GroupResource;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use DomainException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('blocks deleting a group with preinscriptions and preserves payment records', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => fake()->safeEmail(),
    ]);

    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
        'monto' => 100.00,
    ]);

    expect(fn () => $group->delete())
        ->toThrow(DomainException::class, 'No se puede eliminar un grupo que ya contiene preinscripciones o pagos registrados');

    // Verify group and all related financial records remain intact
    expect(Group::whereKey($group->id)->exists())->toBeTrue()
        ->and(Preinscription::whereKey($preinscription->id)->exists())->toBeTrue()
        ->and(Payment::whereKey($payment->id)->exists())->toBeTrue();
});

it('allows deleting an empty group without preinscriptions', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    expect($group->preinscriptions()->count())->toBe(0);

    $groupId = $group->id;
    $group->delete();

    expect(Group::whereKey($groupId)->exists())->toBeFalse();
});

it('hides delete action in GroupResource table for groups that have preinscriptions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);

    $course = Course::factory()->create();
    $groupWithStudents = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Con Alumnos']);
    $emptyGroup = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo Vacio']);

    Preinscription::factory()->inscrito()->create([
        'group_id' => $groupWithStudents->id,
        'email' => fake()->safeEmail(),
    ]);

    $this->actingAs($admin);

    Livewire::test(GroupResource\Pages\ListGroups::class)
        ->assertTableActionHidden('delete', $groupWithStudents)
        ->assertTableActionVisible('delete', $emptyGroup);
});
