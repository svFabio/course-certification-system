<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use App\Services\CertificateService;
use App\Services\CourseService;
use App\Services\EvaluationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('closes a course and generates certificates for enrolled students and the instructor', function () {
    $course = Course::factory()->create([
        'status' => CourseStatus::EN_CURSO,
        'carga_horaria' => '20',
        'attendance_weight' => 0,
    ]);
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo A']);

    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => '2026-03-01',
    ]);
    Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => '2026-03-20',
    ]);

    $passed = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $failed = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Preinscription::factory()->create(['group_id' => $group->id]); // pendiente_pago — ignored

    $eval = app(EvaluationService::class);
    $eval->setCriteria($course, [
        ['nombre' => 'Nota General', 'ponderacion' => 100.0],
    ]);
    $criteriaId = $course->evaluationCriteria()->first()->id;
    $eval->recordGrades($passed, [$criteriaId => 85.0]);
    $eval->recordGrades($failed, [$criteriaId => 40.0]);

    $result = app(CourseService::class)->close($course->fresh());

    expect($result['course']->status)->toBe(CourseStatus::FINALIZADO)
        ->and($group->fresh()->status)->toBe(GroupStatus::FINALIZADO)
        ->and($result['certificates'])->toHaveCount(3);

    $certPassed = Certificate::where('preinscription_id', $passed->id)->first();
    $certFailed = Certificate::where('preinscription_id', $failed->id)->first();
    $certInstructor = Certificate::query()
        ->where('course_id', $course->id)
        ->where('tipo', CertificateType::INSTRUCTOR)
        ->first();

    expect($certPassed)->not->toBeNull()
        ->and($certPassed->tipo)->toBe(CertificateType::APROBACION)
        ->and($certPassed->signature_status)->toBe(SignatureStatus::PENDIENTE)
        ->and($certPassed->codigo_unico)->toMatch('/^CERT-\d{4}-[A-Z0-9]{8}$/')
        ->and($certFailed->tipo)->toBe(CertificateType::ASISTENCIA)
        ->and($certInstructor)->not->toBeNull()
        ->and($certInstructor->instructor_id)->toBe($course->instructor_id)
        ->and($certInstructor->grupos_detalle)->toContain('Grupo A');
});

it('does not create duplicate certificates when regenerating for a closed course', function () {
    $course = Course::factory()->create([
        'status' => CourseStatus::EN_CURSO,
        'attendance_weight' => 0,
    ]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    $eval = app(EvaluationService::class);
    $eval->setCriteria($course, [['nombre' => 'Nota', 'ponderacion' => 100.0]]);
    $eval->recordGrades($preinscription, [$course->evaluationCriteria()->first()->id => 90.0]);

    app(CourseService::class)->close($course->fresh());

    $secondBatch = app(CertificateService::class)->generateForCourse($course->fresh());

    expect($secondBatch)->toHaveCount(0)
        ->and(Certificate::where('course_id', $course->id)->count())->toBe(2);
});

it('rejects closing a course that is already finalized', function () {
    $course = Course::factory()->create(['status' => CourseStatus::FINALIZADO]);

    expect(fn () => app(CourseService::class)->close($course))
        ->toThrow(ValidationException::class);
});

it('rejects generating a certificate for a non-enrolled preinscription', function () {
    $course = Course::factory()->create(['attendance_weight' => 0]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    expect(fn () => app(CertificateService::class)->generate($preinscription))
        ->toThrow(ValidationException::class);
});

it('builds a pdf payload with workload, course dates and verification qr when ready', function () {
    $course = Course::factory()->create([
        'carga_horaria' => '30',
        'attendance_weight' => 0,
    ]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    Session::factory()->create(['group_id' => $group->id, 'fecha' => '2026-04-01']);
    Session::factory()->create(['group_id' => $group->id, 'fecha' => '2026-04-15']);

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => fake()->firstName(),
        'apellido_paterno' => fake()->lastName(),
    ]);

    $eval = app(EvaluationService::class);
    $eval->setCriteria($course, [['nombre' => 'Nota', 'ponderacion' => 100.0]]);
    $eval->recordGrades($preinscription, [$course->evaluationCriteria()->first()->id => 80.0]);

    $service = app(CertificateService::class);
    $certificate = $service->generate($preinscription);
    $certificate->update(['signature_status' => SignatureStatus::LISTO]);

    $dates = $service->courseDateRange($course->fresh());
    expect($dates['rango'])->toBe('01/04/2026 – 15/04/2026');

    $pdf = $service->generatePdf($certificate->fresh());
    $output = $pdf->output();

    expect($output)->toStartWith('%PDF')
        ->and($service->verificationUrl($certificate))->toContain($certificate->codigo_unico);
});

it('keeps unique alphanumeric certificate codes across many generations', function () {
    $course = Course::factory()->create(['attendance_weight' => 0, 'status' => CourseStatus::EN_CURSO]);
    $group = Group::factory()->create(['course_id' => $course->id]);

    $eval = app(EvaluationService::class);
    $eval->setCriteria($course, [['nombre' => 'Nota', 'ponderacion' => 100.0]]);
    $criteriaId = $course->evaluationCriteria()->first()->id;

    $students = Preinscription::factory()->inscrito()->count(5)->create(['group_id' => $group->id]);
    foreach ($students as $student) {
        $eval->recordGrades($student, [$criteriaId => 75.0]);
    }

    $certs = app(CourseService::class)->close($course->fresh())['certificates'];
    $codes = $certs->pluck('codigo_unico');

    expect($codes)->toHaveCount(6)
        ->and($codes->unique())->toHaveCount(6)
        ->and($codes->every(fn (string $code) => (bool) preg_match('/^CERT-\d{4}-[A-Z0-9]{8}$/', $code)))->toBeTrue();
});

it('advances the signature flow and notifies when the certificate becomes ready', function () {
    Notification::fake();

    $student = User::factory()->create();
    $course = Course::factory()->create(['attendance_weight' => 0]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $eval = app(EvaluationService::class);
    $eval->setCriteria($course, [['nombre' => 'Nota', 'ponderacion' => 100.0]]);
    $eval->recordGrades($preinscription, [$course->evaluationCriteria()->first()->id => 90.0]);

    $service = app(CertificateService::class);
    $certificate = $service->generate($preinscription);

    expect($certificate->signature_status)->toBe(SignatureStatus::PENDIENTE);

    $certificate = $service->advanceSignature($certificate);
    expect($certificate->signature_status)->toBe(SignatureStatus::FIRMADO_JEFE_DEPTO);

    $certificate = $service->advanceSignature($certificate);
    expect($certificate->signature_status)->toBe(SignatureStatus::FIRMADO_DIRECTOR_ACADEMICO);

    $certificate = $service->advanceSignature($certificate);
    expect($certificate->signature_status)->toBe(SignatureStatus::FIRMADO_DECANO);

    $certificate = $service->advanceSignature($certificate);
    expect($certificate->signature_status)->toBe(SignatureStatus::LISTO)
        ->and($certificate->listo_en)->not->toBeNull()
        ->and($certificate->pdf_path)->toBe('certificates/'.$certificate->codigo_unico.'.pdf')
        ->and($certificate->administrativeDelayLabel())->not->toBe('—');

    Storage::disk('cloudinary')->assertExists($certificate->pdf_path);

    Notification::assertSentTo($student, CertificateReadyNotification::class);

    $service->resendReadyNotification($certificate);
    Notification::assertSentToTimes($student, CertificateReadyNotification::class, 2);
});

it('persists certificate pdf to cloudinary when reaching listo status via updateSignatureStatus', function () {
    Notification::fake();

    $student = User::factory()->create();
    $course = Course::factory()->create(['attendance_weight' => 0]);
    $group = Group::factory()->create(['course_id' => $course->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);

    $service = app(CertificateService::class);
    $certificate = $service->generate($preinscription);

    expect($certificate->pdf_path)->toBeNull();

    $certificate = $service->updateSignatureStatus($certificate, SignatureStatus::LISTO);

    expect($certificate->pdf_path)->toBe('certificates/'.$certificate->codigo_unico.'.pdf');
    Storage::disk('cloudinary')->assertExists($certificate->pdf_path);
});

it('can emit one instructor certificate per group', function () {
    $course = Course::factory()->create(['status' => CourseStatus::FINALIZADO]);
    Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo A']);
    Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo B']);

    $created = app(CertificateService::class)->generateInstructorCertificates($course->fresh(), onePerGroup: true);

    expect($created)->toHaveCount(2)
        ->and(Certificate::where('course_id', $course->id)->where('tipo', CertificateType::INSTRUCTOR)->count())->toBe(2);
});
