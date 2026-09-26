<?php

declare(strict_types=1);

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Filament\Admin\Resources\GroupResource\Pages\CreateGroup;
use App\Filament\Admin\Resources\GroupResource\Pages\ListGroups;
use App\Mail\GroupMergedNotification;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
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
