<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentRejectedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeRefundFlowPreinscription(PreinscriptionStatus $status): Preinscription
{
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_maximo' => 30,
    ]);

    return Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => $status,
        'fotocopia_ci' => true,
    ]);
}

it('moves the preinscription and its verified payment to devolucion_pendiente when a refund is requested', function () {
    Mail::fake();

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $verifiedPayment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
        'motivo_rechazo' => 'Comprobante ilegible',
    ]);
    $pendingPayment = Payment::factory()->pending()->create([
        'preinscription_id' => $preinscription->id,
    ]);
    $rejectedPayment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'estado' => PaymentStatus::RECHAZADO,
        'motivo_rechazo' => 'Comprobante falso',
    ]);

    app(PaymentService::class)->requestRefund($preinscription, 'No alcanzó el cupo mínimo');

    // Only the verified payment moves to devolucion_pendiente; pendiente and
    // rechazado payments are left untouched by the refund request.
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE)
        ->and($verifiedPayment->fresh()->estado)->toBe(PaymentStatus::DEVOLUCION_PENDIENTE)
        ->and($verifiedPayment->fresh()->motivo_devolucion)->toBe('No alcanzó el cupo mínimo')
        ->and($verifiedPayment->fresh()->motivo_rechazo)->toBeNull()
        ->and($pendingPayment->fresh()->estado)->toBe(PaymentStatus::PENDIENTE)
        ->and($pendingPayment->fresh()->motivo_devolucion)->toBeNull()
        ->and($rejectedPayment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO)
        ->and($rejectedPayment->fresh()->motivo_rechazo)->toBe('Comprobante falso');

    Mail::assertNothingQueued();
});

it('blocks a refund request unless the preinscription is inscrito', function (PreinscriptionStatus $status) {
    $preinscription = makeRefundFlowPreinscription($status);
    $payment = Payment::factory()->pending()->create([
        'preinscription_id' => $preinscription->id,
    ]);

    expect(fn () => app(PaymentService::class)->requestRefund($preinscription, 'Motivo'))
        ->toThrow(ValidationException::class, 'Solo se puede solicitar una devolución para participantes inscritos.');

    // The rejection must leave every stored field untouched.
    expect($preinscription->fresh()->status)->toBe($status)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::PENDIENTE)
        ->and($payment->fresh()->motivo_devolucion)->toBeNull()
        ->and($payment->fresh()->motivo_rechazo)->toBeNull();
})->with([
    'pendiente de pago' => PreinscriptionStatus::PENDIENTE_PAGO,
    'retirado' => PreinscriptionStatus::RETIRADO,
    'rechazado' => PreinscriptionStatus::RECHAZADO,
    'devolucion pendiente' => PreinscriptionStatus::DEVOLUCION_PENDIENTE,
    'reembolsado' => PreinscriptionStatus::REEMBOLSADO,
]);

it('marks the preinscription and its payment as reembolsado when the refund is confirmed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);
    $service = app(PaymentService::class);
    $service->requestRefund($preinscription, 'Cancelación de curso');

    $service->confirmRefund($preinscription);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::REEMBOLSADO)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::REEMBOLSADO)
        ->and($payment->fresh()->reembolsado_por)->toBe($user->id)
        ->and($payment->fresh()->reembolsado_en)->not->toBeNull()
        ->and($payment->fresh()->motivo_devolucion)->toBe('Cancelación de curso');
});

it('blocks refund confirmation while the preinscription is still inscrito', function () {
    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);

    expect(fn () => app(PaymentService::class)->confirmRefund($preinscription))
        ->toThrow(ValidationException::class, 'La preinscripción debe estar en estado "devolución pendiente" para confirmar el reembolso.');

    // The rejection must leave every stored field untouched.
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO)
        ->and($payment->fresh()->reembolsado_por)->toBeNull()
        ->and($payment->fresh()->reembolsado_en)->toBeNull();
});

it('blocks confirming a refund that has already been confirmed', function () {
    $this->actingAs(User::factory()->create());

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);
    $service = app(PaymentService::class);
    $service->requestRefund($preinscription, 'Motivo válido');
    $service->confirmRefund($preinscription);

    expect(fn () => $service->confirmRefund($preinscription))
        ->toThrow(ValidationException::class, 'La preinscripción debe estar en estado "devolución pendiente" para confirmar el reembolso.');

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::REEMBOLSADO)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::REEMBOLSADO)
        ->and($payment->fresh()->reembolsado_por)->not->toBeNull();
});

it('never mirrors refund payment states onto the preinscription from the payment observer', function () {
    Mail::fake();

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);

    // A payment entering a refund state must not touch the preinscription:
    // PaymentService owns the refund transitions, not the observer.
    $payment->update(['estado' => PaymentStatus::DEVOLUCION_PENDIENTE]);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    $payment->update(['estado' => PaymentStatus::REEMBOLSADO]);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    Mail::assertNothingQueued();
});

it('keeps a refund-state preinscription untouched when its payment becomes verificado again', function () {
    Mail::fake();

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);
    app(PaymentService::class)->requestRefund($preinscription, 'Desistimiento temporal');

    // The mass update bypasses model events, so refresh before updating to
    // actually fire PaymentObserver::updated with the new estado.
    $payment = $payment->fresh();
    $payment->update(['estado' => PaymentStatus::VERIFICADO]);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO);

    Mail::assertNothingQueued();
});

it('keeps a refund-state preinscription untouched when its payment is rejected', function () {
    Mail::fake();

    $preinscription = makeRefundFlowPreinscription(PreinscriptionStatus::INSCRITO);
    $payment = Payment::factory()->verified()->create([
        'preinscription_id' => $preinscription->id,
    ]);
    app(PaymentService::class)->requestRefund($preinscription, 'Desistimiento temporal');

    // The mass update bypasses model events, so refresh before updating to
    // actually fire PaymentObserver::updated with the new estado.
    $payment = $payment->fresh();
    $payment->update([
        'estado' => PaymentStatus::RECHAZADO,
        'motivo_rechazo' => 'Comprobante ilegible',
    ]);

    // Terminal/refund states are a no-op for the payment observer: the
    // preinscription stays in the refund flow and the student is not notified.
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE)
        ->and($payment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO)
        ->and($payment->fresh()->motivo_rechazo)->toBe('Comprobante ilegible');

    Mail::assertNotQueued(PaymentRejectedNotification::class);
});
