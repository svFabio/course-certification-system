<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\MergeParticipantDecision;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource\Pages\CreateGroup;
use App\Filament\Admin\Resources\GroupResource\Pages\ListGroups;
use App\Mail\GroupMergedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\GroupMergeService;
use App\Services\PaymentService;
use App\Services\PreinscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

it('moves active preinscriptions to the destination, closes the origin and queues one merged email per moved student', function () {
    Mail::fake();
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $movedPendiente = Preinscription::factory()->create(['group_id' => $origin->id]);
    $movedInscrito = Preinscription::factory()->inscrito()->create(['group_id' => $origin->id]);
    $rechazado = Preinscription::factory()->create([
        'group_id' => $origin->id,
        'status' => PreinscriptionStatus::RECHAZADO,
    ]);
    $retirado = Preinscription::factory()->retirado()->create(['group_id' => $origin->id]);

    Livewire::test(ListGroups::class)
        ->callTableAction('fusionarGrupo', $origin, ['grupo_destino_id' => $destination->id]);

    expect($origin->fresh()->status)->toBe(GroupStatus::CERRADO);
    expect($movedPendiente->fresh()->group_id)->toBe($destination->id);
    expect($movedInscrito->fresh()->group_id)->toBe($destination->id);
    expect($rechazado->fresh()->group_id)->toBe($origin->id);
    expect($retirado->fresh()->group_id)->toBe($origin->id);

    Mail::assertQueuedCount(2);
    Mail::assertQueued(
        GroupMergedNotification::class,
        fn (GroupMergedNotification $mail): bool => $mail->hasTo($movedPendiente->email),
    );
    Mail::assertQueued(
        GroupMergedNotification::class,
        fn (GroupMergedNotification $mail): bool => $mail->hasTo($movedInscrito->email),
    );
});

it('rejects fusion when the destination does not have enough capacity and moves nothing', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 2,
    ]);

    Preinscription::factory()->count(3)->create(['group_id' => $origin->id]);

    Livewire::test(ListGroups::class)
        ->callTableAction('fusionarGrupo', $origin, ['grupo_destino_id' => $destination->id])
        ->assertHasErrors([
            'grupo_destino_id' => 'El grupo destino no tiene cupo disponible para 3 participante(s).',
        ]);

    expect($origin->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);
    expect(Preinscription::query()->where('group_id', $destination->id)->count())->toBe(0);
    expect(
        Preinscription::query()
            ->where('group_id', $origin->id)
            ->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])
            ->count()
    )->toBe(3);
});

it('rejects a destination group that belongs to a different course', function () {
    $this->actingAs($this->admin);

    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $origin = Group::factory()->noHabilitado()->create(['course_id' => $courseA->id]);
    $foreignDestination = Group::factory()->habilitado()->create(['course_id' => $courseB->id]);

    Preinscription::factory()->create(['group_id' => $origin->id]);

    Livewire::test(ListGroups::class)
        ->callTableAction('fusionarGrupo', $origin, ['grupo_destino_id' => $foreignDestination->id])
        ->assertHasErrors(['grupo_destino_id']);

    expect($origin->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);
    expect(Preinscription::query()->where('group_id', $foreignDestination->id)->count())->toBe(0);
    expect(Preinscription::query()->where('group_id', $origin->id)->count())->toBe(1);
});

it('creates groups with no_habilitado status by default through the Filament create page', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();

    Livewire::test(CreateGroup::class)
        ->fillForm([
            'course_id' => $course->id,
            'nombre' => 'Grupo Nuevo Por Habilitar',
            'aula' => 'Laboratorio 1',
            'hora_inicio' => '09:45',
            'cupo_minimo' => 15,
            'cupo_maximo' => 30,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $group = Group::query()->where('nombre', 'Grupo Nuevo Por Habilitar')->firstOrFail();

    expect($group->status)->toBe(GroupStatus::NO_HABILITADO);
});

it('auto-enables a no_habilitado group when active preinscriptions reach cupo_minimo', function () {
    $group = Group::factory()->noHabilitado()->create([
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    Preinscription::factory()->create(['group_id' => $group->id]);
    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);

    Preinscription::factory()->create(['group_id' => $group->id]);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('never auto-enables a closed group', function () {
    $group = Group::factory()->cerrado()->create([
        'cupo_minimo' => 1,
        'cupo_maximo' => 10,
    ]);

    Preinscription::factory()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::CERRADO);
});

it('leaves an already enabled group at the minimum untouched', function () {
    $group = Group::factory()->habilitado()->create([
        'cupo_minimo' => 1,
        'cupo_maximo' => 10,
    ]);
    $preinscription = Preinscription::factory()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('merges group with an enrolled student requesting refund due to schedule incompatibility', function () {
    Mail::fake();
    $course = Course::factory()->withHours('20')->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $enrolled = Preinscription::factory()->inscrito()->create(['group_id' => $origin->id]);
    Payment::factory()->create([
        'preinscription_id' => $enrolled->id,
        'monto' => 100.0,
        'estado' => PaymentStatus::VERIFICADO,
    ]);

    $moved = Preinscription::factory()->create(['group_id' => $origin->id]);

    $result = app(GroupMergeService::class)->assistedMerge(
        $origin,
        $destination,
        [
            $enrolled->id => MergeParticipantDecision::REEMBOLSO->value,
            $moved->id => MergeParticipantDecision::TRASLADAR->value,
        ]
    );

    expect($result['movedCount'])->toBe(1);
    expect($result['refundedCount'])->toBe(1);
    expect($result['withdrawnCount'])->toBe(0);

    expect($origin->fresh()->status)->toBe(GroupStatus::CERRADO);
    expect($moved->fresh()->group_id)->toBe($destination->id);

    // Enrolled student requesting refund should have status DEVOLUCION_PENDIENTE and payment DEVOLUCION_PENDIENTE
    expect($enrolled->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE);
    expect($enrolled->payments()->first()->estado)->toBe(PaymentStatus::DEVOLUCION_PENDIENTE);
    expect($enrolled->payments()->first()->motivo_devolucion)->toBe('Incompatibilidad de horario por fusión de grupo');

    Mail::assertQueuedCount(1);
    Mail::assertQueued(
        GroupMergedNotification::class,
        fn (GroupMergedNotification $mail): bool => $mail->hasTo($moved->email),
    );
});

it('merges group with a pending payment student who is marked as withdrawn', function () {
    $course = Course::factory()->withHours('20')->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $pending = Preinscription::factory()->create(['group_id' => $origin->id]);
    $moved = Preinscription::factory()->inscrito()->create(['group_id' => $origin->id]);

    $result = app(GroupMergeService::class)->assistedMerge(
        $origin,
        $destination,
        [
            $pending->id => MergeParticipantDecision::RETIRAR->value,
            $moved->id => MergeParticipantDecision::TRASLADAR->value,
        ]
    );

    expect($result['movedCount'])->toBe(1);
    expect($result['refundedCount'])->toBe(0);
    expect($result['withdrawnCount'])->toBe(1);

    expect($origin->fresh()->status)->toBe(GroupStatus::CERRADO);
    expect($moved->fresh()->group_id)->toBe($destination->id);
    expect($pending->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO);
});

it('validates exact destination capacity only against participants who are actually moved', function () {
    $course = Course::factory()->withHours('20')->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    // Destination only has 2 slots available
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 2,
    ]);

    // Origin has 4 active participants: 2 will be moved, 1 refunded, 1 withdrawn
    $student1 = Preinscription::factory()->create(['group_id' => $origin->id]);
    $student2 = Preinscription::factory()->create(['group_id' => $origin->id]);
    $studentRefund = Preinscription::factory()->inscrito()->create(['group_id' => $origin->id]);
    Payment::factory()->create([
        'preinscription_id' => $studentRefund->id,
        'monto' => 100.0,
        'estado' => PaymentStatus::VERIFICADO,
    ]);
    $studentWithdraw = Preinscription::factory()->create(['group_id' => $origin->id]);

    // Destination has max capacity of 2. 4 total students, but only 2 toMove.
    // It should SUCCEED because only 2 are moved.
    $result = app(GroupMergeService::class)->assistedMerge(
        $origin,
        $destination,
        [
            $student1->id => MergeParticipantDecision::TRASLADAR->value,
            $student2->id => MergeParticipantDecision::TRASLADAR->value,
            $studentRefund->id => MergeParticipantDecision::REEMBOLSO->value,
            $studentWithdraw->id => MergeParticipantDecision::RETIRAR->value,
        ]
    );

    expect($result['movedCount'])->toBe(2);
    expect($result['refundedCount'])->toBe(1);
    expect($result['withdrawnCount'])->toBe(1);
    expect($destination->preinscriptions()->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO])->count())->toBe(2);
});

it('supports assisted merge via filament table action with custom decisions', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $origin = Group::factory()->noHabilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);
    $destination = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 15,
        'cupo_maximo' => 30,
    ]);

    $studentMove = Preinscription::factory()->create(['group_id' => $origin->id]);
    $studentRefund = Preinscription::factory()->inscrito()->create(['group_id' => $origin->id]);
    Payment::factory()->create([
        'preinscription_id' => $studentRefund->id,
        'monto' => 100.0,
        'estado' => PaymentStatus::VERIFICADO,
    ]);

    Livewire::test(ListGroups::class)
        ->callTableAction('fusionarGrupo', $origin, [
            'grupo_destino_id' => $destination->id,
            'participant_decisions' => [
                $studentMove->id => MergeParticipantDecision::TRASLADAR->value,
                $studentRefund->id => MergeParticipantDecision::REEMBOLSO->value,
            ],
        ])
        ->assertHasNoTableActionErrors();

    expect($origin->fresh()->status)->toBe(GroupStatus::CERRADO);
    expect($studentMove->fresh()->group_id)->toBe($destination->id);
    expect($studentRefund->fresh()->status)->toBe(PreinscriptionStatus::DEVOLUCION_PENDIENTE);
});

it('auto-disables a habilitado group when refund request drops active count below cupo_minimo', function () {
    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    // Two enrolled participants — group is just at the threshold.
    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $p2 = Preinscription::factory()->auxiliarWithApprovedCertificate()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::INSCRITO,
        'fotocopia_ci' => true,
    ]);

    // Verify a payment for p2 so requestRefund and cancelRefundRequest can work.
    Payment::factory()->create([
        'preinscription_id' => $p2->id,
        'estado' => PaymentStatus::VERIFICADO,
    ]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    // Requesting refund drops active count from 2 to 1 (< threshold 2), auto-disabling the group.
    app(PaymentService::class)->requestRefund($p2, 'No puede asistir');
    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);

    // Cancelling the refund request restores the participant to INSCRITO (count back to 2), auto-enabling the group.
    app(PaymentService::class)->cancelRefundRequest($p2);
    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);
});

it('auto-disables a habilitado group when markAsWithdrawn drops active count below cupo_minimo', function () {
    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    // Exactly two active participants — group is at the threshold.
    $p1 = Preinscription::factory()->create(['group_id' => $group->id]);
    $p2 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    expect($group->fresh()->status)->toBe(GroupStatus::HABILITADO);

    // Withdrawing p2 drops active count to 1, below the threshold of 2.
    app(PreinscriptionService::class)->markAsWithdrawn($p2);

    expect($group->fresh()->status)->toBe(GroupStatus::NO_HABILITADO);
    expect($p2->fresh()->status)->toBe(PreinscriptionStatus::RETIRADO);
});

it('does not auto-disable a group that is in en_curso status when count drops below threshold', function () {
    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'status' => GroupStatus::EN_CURSO,
        'cupo_minimo' => 2,
        'cupo_maximo' => 10,
    ]);

    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);

    // Only 1 active participant, below threshold — but group is EN_CURSO, must stay.
    expect($group->fresh()->status)->toBe(GroupStatus::EN_CURSO);
});
