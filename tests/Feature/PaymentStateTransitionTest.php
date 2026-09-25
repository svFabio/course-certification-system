<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function makePreinscriptionForPaymentStateTransition(PreinscriptionStatus $status): Preinscription
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

it('promotes a pendiente de pago preinscription to inscrito when its payment is verified', function () {
    Mail::fake();

    $preinscription = makePreinscriptionForPaymentStateTransition(PreinscriptionStatus::PENDIENTE_PAGO);
    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    app(PaymentService::class)->verify($payment);

    expect($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO)
        ->and($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    Mail::assertQueued(PaymentConfirmedNotification::class, function ($mail) use ($preinscription) {
        return $mail->hasTo($preinscription->email);
    });
});

it('keeps a retirado preinscription untouched when a payment is verified', function () {
    Mail::fake();

    $preinscription = makePreinscriptionForPaymentStateTransition(PreinscriptionStatus::RETIRADO);
    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    app(PaymentService::class)->verify($payment);

    expect($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO)
        ->and($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO);

    Mail::assertNothingQueued();
});

it('demotes an inscrito preinscription to rechazado when its only verified payment is rejected', function () {
    $preinscription = makePreinscriptionForPaymentStateTransition(PreinscriptionStatus::PENDIENTE_PAGO);
    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);
    $service = app(PaymentService::class);

    $service->verify($payment);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    $service->reject($payment, 'Comprobante ilegible o adulterado');

    expect($payment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO)
        ->and($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);
});

it('keeps an inscrito preinscription enrolled when another verified payment still gates it', function () {
    Mail::fake();

    $preinscription = makePreinscriptionForPaymentStateTransition(PreinscriptionStatus::PENDIENTE_PAGO);
    $service = app(PaymentService::class);

    $firstPayment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);
    $service->verify($firstPayment);

    $secondPayment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);
    $service->verify($secondPayment);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    $service->reject($firstPayment, 'Pago duplicado');

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO)
        ->and($firstPayment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO)
        ->and($secondPayment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO);
});

it('demotes a pendiente de pago preinscription to rechazado when its payment is rejected', function () {
    $preinscription = makePreinscriptionForPaymentStateTransition(PreinscriptionStatus::PENDIENTE_PAGO);
    $payment = Payment::factory()->pending()->create(['preinscription_id' => $preinscription->id]);

    app(PaymentService::class)->reject($payment, 'Pago no identificado en caja');

    expect($payment->fresh()->estado)->toBe(PaymentStatus::RECHAZADO)
        ->and($payment->fresh()->motivo_rechazo)->toBe('Pago no identificado en caja')
        ->and($payment->fresh()->verificado_por)->toBeNull()
        ->and($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);
});
