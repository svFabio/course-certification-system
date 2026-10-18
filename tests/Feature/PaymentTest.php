<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('marks preinscription as inscrito and queues email when payment is verified', function () {
    Mail::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
    ]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'tipo_participante' => TipoParticipante::UMSS,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $payment = Payment::create([
        'preinscription_id' => $preinscription->id,
        'monto' => BusinessRules::calculatePrice(20, TipoParticipante::UMSS),
        'metodo' => PaymentMethod::QR,
        'estado' => PaymentStatus::PENDIENTE,
    ]);

    // Admin verifies payment
    $payment->update([
        'verificado_por' => $user->id,
        'verificado_en' => now(),
        'estado' => PaymentStatus::VERIFICADO,
    ]);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    Mail::assertQueued(PaymentConfirmedNotification::class, function ($mail) use ($preinscription) {
        return $mail->hasTo($preinscription->email);
    });
});

it('marks preinscription as rechazado when payment is rejected', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $payment = Payment::create([
        'preinscription_id' => $preinscription->id,
        'monto' => 100,
        'metodo' => PaymentMethod::EFECTIVO,
        'estado' => PaymentStatus::PENDIENTE,
    ]);

    $payment->update([
        'estado' => PaymentStatus::RECHAZADO,
        'motivo_rechazo' => 'Comprobante ilegible o adulterado',
    ]);

    expect($payment->fresh()->motivo_rechazo)->toBe('Comprobante ilegible o adulterado');
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::RECHAZADO);
});

it('rejects payment verification when the payment amount does not match the chargeable price', function () {
    $course = Course::factory()->create([
        'precio_umss' => 80.00,
        'precio_externo' => 100.00,
        'precio_auxiliar' => 40.00,
    ]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'tipo_participante' => TipoParticipante::UMSS,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
        'fotocopia_ci' => true,
    ]);

    // Payment has wrong amount (e.g. 50 instead of expected 80)
    $payment = Payment::factory()->pending()->create([
        'preinscription_id' => $preinscription->id,
        'monto' => 50.00,
    ]);

    expect(fn () => app(PaymentService::class)->verify($payment))
        ->toThrow(ValidationException::class, 'El monto correcto para este participante es Bs. 80');

    expect($payment->fresh()->estado)->toBe(PaymentStatus::PENDIENTE);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
});

it('accepts payment verification when the payment amount matches the chargeable price', function () {
    $course = Course::factory()->create([
        'precio_umss' => 80.00,
        'precio_externo' => 100.00,
        'precio_auxiliar' => 40.00,
    ]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'tipo_participante' => TipoParticipante::EXTERNO,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
        'fotocopia_ci' => true,
    ]);

    $payment = Payment::factory()->pending()->create([
        'preinscription_id' => $preinscription->id,
        'monto' => 100.00,
    ]);

    app(PaymentService::class)->verify($payment);

    expect($payment->fresh()->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
});
