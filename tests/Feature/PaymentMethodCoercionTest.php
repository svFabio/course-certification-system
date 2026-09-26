<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeUmssPreinscriptionPendingPayment(): Preinscription
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

    return Preinscription::factory()->umss()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
        'fotocopia_ci' => true,
    ]);
}

it('registers and verifies a payment when given a PaymentMethod enum instance', function () {
    $preinscription = makeUmssPreinscriptionPendingPayment();

    $payment = app(PaymentService::class)->registerAndVerify(
        $preinscription,
        PaymentMethod::EFECTIVO,
        null,
    );

    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->metodo)->toBe(PaymentMethod::EFECTIVO);
    expect($payment->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
});

it('registers and verifies a payment when given a payment method string', function () {
    $preinscription = makeUmssPreinscriptionPendingPayment();

    $payment = app(PaymentService::class)->registerAndVerify(
        $preinscription,
        PaymentMethod::EFECTIVO->value,
        null,
    );

    expect($payment)->toBeInstanceOf(Payment::class);
    expect($payment->metodo)->toBe(PaymentMethod::EFECTIVO);
    expect($payment->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
});
