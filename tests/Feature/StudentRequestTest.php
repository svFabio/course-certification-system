<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\StudentRequestResource;
use App\Filament\Admin\Resources\StudentRequestResource\Pages\ListStudentRequests;
use App\Filament\Student\Resources\StudentPreinscriptionResource\Pages\ListStudentPreinscriptions;
use App\Mail\StudentRequestReceivedNotification;
use App\Mail\StudentRequestResolvedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\StudentRequest;
use App\Models\User;
use App\Services\StudentRequestService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::ADMIN->value);

    $this->student = User::factory()->create();
    $this->student->assignRole(UserRole::STUDENT->value);

    $this->course = Course::factory()->withHours('20')->create(['status' => CourseStatus::PUBLICADO]);

    $this->group = Group::factory()->habilitado()->create([
        'course_id' => $this->course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $this->destination = Group::factory()->habilitado()->create([
        'course_id' => $this->course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);
});

it('lets a student request a group change on their own preinscription and queues the acknowledgement', function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'email' => $this->student->email,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $this->actingAs($this->student);

    Livewire::test(ListStudentPreinscriptions::class)
        ->assertTableActionVisible('solicitarCambioGrupo', $preinscription)
        ->callTableAction('solicitarCambioGrupo', $preinscription, [
            'group_id_destino' => $this->destination->id,
            'motivo' => 'Conflicto de horario laboral',
        ])
        ->assertHasNoTableActionErrors();

    $request = StudentRequest::first();

    expect($request)->not->toBeNull();
    expect($request->estado)->toBe(StudentRequestStatus::PENDIENTE);
    expect($request->tipo)->toBe(StudentRequestType::CAMBIO_GRUPO);
    expect($request->group_id_destino)->toBe($this->destination->id);
    expect($request->resolved_by)->toBeNull();

    Mail::assertQueued(
        StudentRequestReceivedNotification::class,
        fn (StudentRequestReceivedNotification $mail): bool => $mail->hasTo($preinscription->email),
    );
});

it('lets a student request a refund on their own enrolled preinscription', function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $this->group->id,
        'email' => $this->student->email,
    ]);

    $this->actingAs($this->student);

    Livewire::test(ListStudentPreinscriptions::class)
        ->assertTableActionVisible('solicitarDevolucion', $preinscription)
        ->callTableAction('solicitarDevolucion', $preinscription, [
            'motivo' => 'Motivos personales',
        ])
        ->assertHasNoTableActionErrors();

    $request = StudentRequest::first();

    expect($request->tipo)->toBe(StudentRequestType::DEVOLUCION);
    expect($request->estado)->toBe(StudentRequestStatus::PENDIENTE);
    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::INSCRITO);

    Mail::assertQueued(StudentRequestReceivedNotification::class);
});

it('does not let a student request anything on another student preinscription', function () {
    Filament::setCurrentPanel(Filament::getPanel('student'));

    $other = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $this->actingAs($this->student);

    Livewire::test(ListStudentPreinscriptions::class)
        ->callTableAction('solicitarCambioGrupo', $other, [
            'group_id_destino' => $this->destination->id,
            'motivo' => 'Intento ajeno',
        ]);

    expect(StudentRequest::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('rejects a second pending request of the same type for the same preinscription', function () {
    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $service = app(StudentRequestService::class);

    $service->submit($preinscription, StudentRequestType::CAMBIO_GRUPO, $this->destination->id, 'Primera solicitud');

    expect(fn () => $service->submit($preinscription, StudentRequestType::CAMBIO_GRUPO, $this->destination->id, 'Segunda solicitud'))
        ->toThrow(ValidationException::class);

    expect(StudentRequest::count())->toBe(1);
});

it('lets an admin approve a group change request and move the preinscription', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $request = StudentRequest::factory()->cambioGrupo()->create([
        'preinscription_id' => $preinscription->id,
        'group_id_destino' => $this->destination->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListStudentRequests::class)
        ->assertTableActionVisible('aprobar', $request)
        ->callTableAction('aprobar', $request)
        ->assertHasNoTableActionErrors();

    expect($preinscription->fresh()->group_id)->toBe($this->destination->id);
    expect($request->fresh()->estado)->toBe(StudentRequestStatus::APROBADA);
    expect($request->fresh()->resolved_by)->toBe($this->admin->id);
    expect($request->fresh()->resolved_at)->not->toBeNull();

    Mail::assertQueued(
        StudentRequestResolvedNotification::class,
        fn (StudentRequestResolvedNotification $mail): bool => $mail->hasTo($preinscription->email)
            && $mail->studentRequest->estado === StudentRequestStatus::APROBADA,
    );
});

it('lets an admin approve a refund request and mark the preinscription as pending refund', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $this->group->id,
    ]);

    $request = StudentRequest::factory()->devolucion()->create([
        'preinscription_id' => $preinscription->id,
        'motivo' => 'Motivos personales',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListStudentRequests::class)
        ->callTableAction('aprobar', $request)
        ->assertHasNoTableActionErrors();

    expect($preinscription->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE);
    expect($request->fresh()->estado)->toBe(StudentRequestStatus::APROBADA);

    Mail::assertQueued(StudentRequestResolvedNotification::class);
});

it('lets an admin reject a request with a reason and notifies the student', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $request = StudentRequest::factory()->cambioGrupo()->create([
        'preinscription_id' => $preinscription->id,
        'group_id_destino' => $this->destination->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListStudentRequests::class)
        ->callTableAction('rechazar', $request, [
            'motivo_rechazo' => 'El grupo destino no tiene disponibilidad',
        ])
        ->assertHasNoTableActionErrors();

    expect($request->fresh()->estado)->toBe(StudentRequestStatus::RECHAZADA);
    expect($request->fresh()->motivo_rechazo)->toBe('El grupo destino no tiene disponibilidad');
    expect($request->fresh()->resolved_by)->toBe($this->admin->id);

    Mail::assertQueued(
        StudentRequestResolvedNotification::class,
        fn (StudentRequestResolvedNotification $mail): bool => $mail->studentRequest->motivo_rechazo === 'El grupo destino no tiene disponibilidad',
    );
});

it('leaves the request pending when the destination group has no capacity', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $fullDestination = Group::factory()->habilitado()->create([
        'course_id' => $this->course->id,
        'cupo_minimo' => 1,
        'cupo_maximo' => 1,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $fullDestination->id]);

    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $request = StudentRequest::factory()->cambioGrupo()->create([
        'preinscription_id' => $preinscription->id,
        'group_id_destino' => $fullDestination->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListStudentRequests::class)
        ->callTableAction('aprobar', $request);

    expect($request->fresh()->estado)->toBe(StudentRequestStatus::PENDIENTE);
    expect($preinscription->fresh()->group_id)->toBe($this->group->id);
});

it('throws when trying to approve an already resolved request', function () {
    $preinscription = Preinscription::factory()->create([
        'group_id' => $this->group->id,
        'status' => PreinscriptionStatus::INSCRITO,
    ]);

    $request = StudentRequest::factory()->cambioGrupo()->aprobada()->create([
        'preinscription_id' => $preinscription->id,
        'group_id_destino' => $this->destination->id,
    ]);

    expect(fn () => app(StudentRequestService::class)->approve($request, $this->admin))
        ->toThrow(ValidationException::class);
});

it('forbids a non-admin from opening the admin request inbox', function () {
    $this->actingAs($this->student);

    $this->get(StudentRequestResource::getUrl('index', panel: 'admin'))
        ->assertForbidden();
});
