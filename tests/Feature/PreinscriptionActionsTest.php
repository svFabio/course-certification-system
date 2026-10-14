<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource\Pages\ViewGroup;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\PreinscriptionsRelationManager;
use App\Filament\Admin\Resources\PreinscriptionResource\Pages\ListPreinscriptions;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

// ---------------------------------------------------------------------------
// cancelarDevolucion — PreinscriptionResource
// ---------------------------------------------------------------------------

it('cancelarDevolucion action restores preinscription to inscrito and payment to verificado', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'fotocopia_ci' => true,
    ]);
    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::VERIFICADO,
        'monto' => 100.0,
    ]);

    // Trigger a refund request to put the preinscription in DEVOLUCION_PENDIENTE.
    app(PaymentService::class)->requestRefund($preinscription, 'Desistimiento temporal');

    $preinscription->refresh();
    expect($preinscription->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE);

    Livewire::test(ListPreinscriptions::class)
        ->filterTable('status', PreinscriptionStatus::DEVOLUCION_PENDIENTE->value)
        ->assertTableActionVisible('cancelarDevolucion', $preinscription)
        ->callTableAction('cancelarDevolucion', $preinscription)
        ->assertHasNoTableActionErrors();

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
    expect($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($payment->fresh()->motivo_devolucion)->toBeNull();
});

it('cancelarDevolucion action is not visible for a preinscription that is merely inscrito', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    Livewire::test(ListPreinscriptions::class)
        ->assertTableActionHidden('cancelarDevolucion', $preinscription);
});

// ---------------------------------------------------------------------------
// reactivarPreinscripcion — PreinscriptionsRelationManager
// ---------------------------------------------------------------------------

it('reactivarPreinscripcion action restores a rejected preinscription to pendiente_pago and resets rejected payment', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::RECHAZADO,
        'motivo_rechazo' => 'Comprobante ilegible',
    ]);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->callTableAction('reactivarPreinscripcion', $preinscription)
        ->assertHasNoTableActionErrors();

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
    expect($payment->fresh()->estado)->toBe(PaymentStatus::PENDIENTE);
    expect($payment->fresh()->motivo_rechazo)->toBeNull();
});

it('reactivarPreinscripcion triggers auto-enable when group was no_habilitado and count meets threshold', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    // One already-active participant (PENDIENTE_PAGO).
    Preinscription::factory()->create(['group_id' => $group->id]);

    // One rejected participant that will be reactivated.
    $rejected = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->callTableAction('reactivarPreinscripcion', $rejected)
        ->assertHasNoTableActionErrors();

    // Reactivation brings active count to 2 = cupo_minimo, so group must auto-enable.
    expect($rejected->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('reactivarPreinscripcion action is not visible for a preinscription that is pendiente_pago', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertTableActionHidden('reactivarPreinscripcion', $preinscription);
});

it('reactivarPreinscripcion action restores a retired preinscription to pendiente_pago', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $retired = Preinscription::factory()->retirado()->create(['group_id' => $group->id]);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertTableActionVisible('reactivarPreinscripcion', $retired)
        ->callTableAction('reactivarPreinscripcion', $retired)
        ->assertHasNoTableActionErrors();

    expect($retired->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
});

it('rejects reactivating a retired preinscription when the group has no available capacity', function () {
    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 1,
        'cupo_maximo' => 1,
    ]);

    Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $retired = Preinscription::factory()->retirado()->create(['group_id' => $group->id]);

    expect(fn () => app(PreinscriptionService::class)->reactivate($retired))
        ->toThrow(ValidationException::class, 'capacidad máxima');

    expect($retired->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO);
});

it('rejects reactivating a preinscription whose group is closed', function () {
    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->cerrado()->create(['course_id' => $course->id]);

    $rejected = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);

    expect(fn () => app(PreinscriptionService::class)->reactivate($rejected))
        ->toThrow(ValidationException::class, 'no está disponible');

    expect($rejected->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);
});

// ---------------------------------------------------------------------------
// default table filter — PreinscriptionResource
// ---------------------------------------------------------------------------

it('lists pendiente_pago preinscriptions by default without applying a status filter', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $pending = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);
    $enrolled = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    Livewire::test(ListPreinscriptions::class)
        ->assertCanSeeTableRecords([$pending, $enrolled]);
});
