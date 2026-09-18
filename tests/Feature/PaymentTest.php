<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

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
        'tipo_participante' => 'umss',
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $payment = Payment::create([
        'preinscription_id' => $preinscription->id,
        'monto' => BusinessRules::calculatePrice(20, 'umss'),
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
