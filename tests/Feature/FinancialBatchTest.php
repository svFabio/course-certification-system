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
use App\Models\User;
use App\Services\BoletaService;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeAuxiliarPreinscription(string $approved = 'pending', int $courseId = 20): Preinscription
{
    $course = Course::factory()->create([
        'carga_horaria' => (string) $courseId,
        'status' => CourseStatus::PUBLICADO,
    ]);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_maximo' => 30,
    ]);

    $state = match ($approved) {
        'approved' => 'auxiliarWithApprovedCertificate',
        default => 'auxiliar',
    };

    return Preinscription::factory()->{$state}()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);
}

it('charges umss price to an auxiliar whose certificate has not been approved', function () {
    $preinscription = makeAuxiliarPreinscription('pending');

    expect($preinscription->chargeable_price)->toBe((float) BusinessRules::PRICING['20']['umss']);
});

it('charges the auxiliar discounted price once the certificate is approved', function () {
    $preinscription = makeAuxiliarPreinscription('approved');

    expect($preinscription->chargeable_price)->toBe((float) BusinessRules::PRICING['20']['auxiliar']);
});

it('blocks payment verification when auxiliar certificate has not been approved', function () {
    $preinscription = makeAuxiliarPreinscription('pending');

    expect(fn () => app(PaymentService::class)->registerAndVerify(
        $preinscription,
        PaymentMethod::EFECTIVO->value,
        'R-0001',
    ))->toThrow(ValidationException::class);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::PENDIENTE_PAGO);
    expect(Payment::where('preinscription_id', $preinscription->id)->count())->toBe(0);
});

it('registers and verifies payment at auxiliar price once the certificate is approved', function () {
    $preinscription = makeAuxiliarPreinscription('approved');

    $payment = app(PaymentService::class)->registerAndVerify(
        $preinscription,
        PaymentMethod::QR->value,
        'QR-2026-AUX',
    );

    expect((float) $payment->monto)->toBe((float) BusinessRules::PRICING['20']['auxiliar']);
    expect($payment->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
});

it('requests a refund on an enrolled preinscription', function () {
    $preinscription = makeAuxiliarPreinscription('approved');
    $user = User::factory()->create();
    app(PaymentService::class)->registerAndVerify($preinscription, PaymentMethod::EFECTIVO->value, 'R-1');

    app(PaymentService::class)->requestRefund($preinscription, 'No alcanzó el cupo mínimo');

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE);
    expect(Payment::where('preinscription_id', $preinscription->id)->first()->estado)
        ->toBe(PaymentStatus::DEVOLUCION_PENDIENTE);
    expect(Payment::where('preinscription_id', $preinscription->id)->first()->motivo_devolucion)
        ->toBe('No alcanzó el cupo mínimo');
});

it('confirms the refund and marks the payment as reembolsado', function () {
    $preinscription = makeAuxiliarPreinscription('approved');
    $user = User::factory()->create();
    $this->actingAs($user);
    app(PaymentService::class)->registerAndVerify($preinscription, PaymentMethod::EFECTIVO->value, 'R-2');
    app(PaymentService::class)->requestRefund($preinscription, 'Cancelación de curso');

    app(PaymentService::class)->confirmRefund($preinscription);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::REEMBOLSADO);
    $payment = Payment::where('preinscription_id', $preinscription->id)->first();
    expect($payment->estado)->toBe(PaymentStatus::REEMBOLSADO);
    expect($payment->reembolsado_por)->toBe($user->id);
    expect($payment->reembolsado_en)->not->toBeNull();
});

it('cannot request a refund twice on the same preinscription', function () {
    $preinscription = makeAuxiliarPreinscription('approved');
    app(PaymentService::class)->registerAndVerify($preinscription, PaymentMethod::EFECTIVO->value, 'R-3');
    app(PaymentService::class)->requestRefund($preinscription, 'Motivo');

    expect(fn () => app(PaymentService::class)->requestRefund($preinscription, 'Otro motivo'))
        ->toThrow(ValidationException::class);
});

it('cancels a pending refund and keeps the participant enrolled', function () {
    $preinscription = makeAuxiliarPreinscription('approved');
    app(PaymentService::class)->registerAndVerify($preinscription, PaymentMethod::EFECTIVO->value, 'R-4');
    app(PaymentService::class)->requestRefund($preinscription, 'Cambio de opinión');

    app(PaymentService::class)->cancelRefundRequest($preinscription);

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);
    $payment = Payment::where('preinscription_id', $preinscription->id)->first();
    expect($payment->estado)->toBe(PaymentStatus::VERIFICADO);
    expect($payment->motivo_devolucion)->toBeNull();
});

it('moves a preinscription to another habilitated group of the same course', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '20',
        'status' => CourseStatus::PUBLICADO,
    ]);
    $origin = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::HABILITADO,
        'cupo_maximo' => 30,
    ]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $origin->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $service = app(PreinscriptionService::class);
    $service->moveToGroup($preinscription, $destination);

    expect($preinscription->fresh()->group_id)->toBe($destination->id);
});

it('rejects moving a preinscription to a group of another course', function () {
    $courseA = Course::factory()->create(['carga_horaria' => '20']);
    $courseB = Course::factory()->create(['carga_horaria' => '30']);
    $origin = Group::factory()->create(['course_id' => $courseA->id]);
    $destination = Group::factory()->create(['course_id' => $courseB->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $origin->id]);

    expect(fn () => app(PreinscriptionService::class)->moveToGroup($preinscription, $destination))
        ->toThrow(ValidationException::class);
});

it('rejects moving a preinscription to a full group', function () {
    $course = Course::factory()->create(['carga_horaria' => '20']);
    $origin = Group::factory()->create(['course_id' => $course->id, 'cupo_maximo' => 30]);
    $destination = Group::factory()->create(['course_id' => $course->id, 'cupo_maximo' => 1]);
    $preinscription = Preinscription::factory()->create([
        'group_id' => $origin->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);
    Preinscription::factory()->create([
        'group_id' => $destination->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    expect(fn () => app(PreinscriptionService::class)->moveToGroup($preinscription, $destination))
        ->toThrow(ValidationException::class);
});

it('generates a downloadable boleta with a correlative for verified payment', function () {
    $preinscription = makeAuxiliarPreinscription('approved');
    app(PaymentService::class)->registerAndVerify($preinscription, PaymentMethod::QR->value, 'QR-BOLETA');

    $service = app(BoletaService::class);
    $response = $service->download($preinscription);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($service->correlative($preinscription))->toMatch('/^BOL-\d{4}-\d{5}$/');
});

it('stores an auxiliar certificate file to the cloudinary disk', function () {
    Storage::fake('cloudinary');

    $ci = '7894561';
    $file = UploadedFile::fake()->create('certificado.pdf', 200, 'application/pdf');
    $path = $file->store(preg_replace('/[^a-zA-Z0-9]+/', '-', mb_strtolower($ci)), 'cloudinary');

    Storage::disk('cloudinary')->assertExists($path);
});
