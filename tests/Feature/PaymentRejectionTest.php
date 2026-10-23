<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\GroupResource\Pages\ViewGroup;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\PreinscriptionsRelationManager;
use App\Mail\PaymentRejectedNotification;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::ADMIN->value);
});

function makeRejectionTestGroup(): Group
{
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);

    return Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 1,
        'cupo_maximo' => 1,
        'status' => GroupStatus::HABILITADO,
    ]);
}

it('rejects a verified payment through the relation manager action and notifies the student', function () {
    Mail::fake();
    $this->actingAs($this->admin);

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    // Creating a verified payment auto-enrolls the student; we then force the
    // preinscription back to pendiente_pago to exercise the rejection path.
    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::VERIFICADO,
    ]);
    $preinscription->refresh();
    $preinscription->update(['status' => PreinscriptionStatus::PENDIENTE_PAGO]);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertTableActionVisible('rechazarPago', $preinscription)
        ->callTableAction('rechazarPago', $preinscription, [
            'motivo_rechazo' => 'Comprobante falsificado',
        ])
        ->assertHasNoTableActionErrors();

    expect($payment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO);
    expect($payment->fresh()->motivo_rechazo)->toBe('Comprobante falsificado');
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);
    expect(app(PreinscriptionService::class)->availableSlots($group))->toBe(1);

    Mail::assertQueued(
        PaymentRejectedNotification::class,
        fn (PaymentRejectedNotification $mail): bool => $mail->hasTo($preinscription->email)
            && $mail->payment->motivo_rechazo === 'Comprobante falsificado',
    );
});

it('hides the reject payment action when the preinscription has no pending or verified payment', function () {
    $this->actingAs($this->admin);

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])->assertTableActionHidden('rechazarPago', $preinscription);
});

it('hides the reject payment action when the student already has an emitted certificate', function () {
    $this->actingAs($this->admin);

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::VERIFICADO,
    ]);
    Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $group->course_id,
    ]);

    Livewire::test(PreinscriptionsRelationManager::class, [
        'ownerRecord' => $group->fresh(),
        'pageClass' => ViewGroup::class,
    ])->assertTableActionHidden('rechazarPago', $preinscription->fresh());
});

it('refuses to reject a payment once the certificate was issued', function () {
    Mail::fake();

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::VERIFICADO,
    ]);
    Certificate::factory()->create([
        'preinscription_id' => $preinscription->id,
        'course_id' => $group->course_id,
    ]);

    expect(fn () => app(PaymentService::class)->reject($payment, 'Pago duplicado'))
        ->toThrow(ValidationException::class);

    expect($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO)
        ->and($payment->fresh()->motivo_rechazo)->toBeNull()
        ->and($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    Mail::assertNothingQueued();
});

it('notifies when rejecting the only verified payment of an enrolled preinscription', function () {
    Mail::fake();

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::VERIFICADO,
    ]);

    app(PaymentService::class)->reject($payment, 'Pago duplicado');

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);

    Mail::assertQueued(
        PaymentRejectedNotification::class,
        fn (PaymentRejectedNotification $mail): bool => $mail->hasTo($preinscription->email)
            && $mail->payment->motivo_rechazo === 'Pago duplicado',
    );
});

it('does not notify the student when the rejected payment does not change the preinscription state', function () {
    Mail::fake();

    $group = makeRejectionTestGroup();
    $preinscription = Preinscription::factory()->retirado()->create(['group_id' => $group->id]);
    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    app(PaymentService::class)->reject($payment, 'Comprobante ilegible');

    expect($payment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO);

    Mail::assertNotQueued(PaymentRejectedNotification::class);
    Mail::assertNothingQueued();
});

it('still renders the rejection notification when the preinscription relation is missing', function () {
    // group_id is NOT NULL, so the missing-relation path is exercised with an
    // unsaved payment that carries no preinscription at all.
    $mail = new PaymentRejectedNotification(new Payment([
        'monto' => 150,
        'motivo_rechazo' => 'Comprobante ilegible',
    ]));

    expect($mail->envelope()->subject)->toBe('Pago rechazado');
    expect($mail->render())->toContain('Comprobante ilegible');
});
