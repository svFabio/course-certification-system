<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\AttendanceResource;
use App\Filament\Admin\Resources\GroupResource;
use App\Filament\Admin\Resources\GroupResource\Pages\ViewGroup;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\PreinscriptionsRelationManager;
use App\Filament\Admin\Resources\GroupResource\RelationManagers\SessionsRelationManager;
use App\Filament\Admin\Resources\PaymentResource;
use App\Filament\Admin\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Admin\Resources\SessionResource;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::ADMIN->value);
});

it('hides orphan attendance and session crud tables from the admin sidebar navigation', function () {
    expect(AttendanceResource::shouldRegisterNavigation())->toBeFalse()
        ->and(AttendanceResource::canCreate())->toBeFalse()
        ->and(SessionResource::shouldRegisterNavigation())->toBeFalse()
        ->and(SessionResource::canCreate())->toBeFalse();
});

it('configures PaymentResource as a read-only audit and cashflow inspection tool', function () {
    $this->actingAs($this->admin);

    $payment = Payment::factory()->create([
        'estado' => PaymentStatus::VERIFICADO,
        'metodo' => PaymentMethod::QR,
    ]);

    expect(PaymentResource::canCreate())->toBeFalse()
        ->and(PaymentResource::canDelete($payment))->toBeFalse()
        ->and(PaymentResource::canEdit($payment))->toBeFalse()
        ->and(PaymentResource::getNavigationGroup())->toBe('Caja & Tesorería')
        ->and(PaymentResource::getNavigationLabel())->toBe('Auditoría de Pagos');
});

it('displays payment audit table with course, group, participant and verifier eager-loaded', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create(['nombre' => 'Ciberseguridad Aplicada']);
    $group = Group::factory()->habilitado()->create(['course_id' => $course->id, 'nombre' => 'G1']);
    $studentFirstName = fake()->firstName();
    $studentLastName = fake()->lastName();
    $studentCi = (string) fake()->numerify('########');

    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => $studentFirstName,
        'apellido_paterno' => $studentLastName,
        'ci' => $studentCi,
    ]);
    $payment = Payment::factory()->create([
        'preinscription_id' => $preinscription->id,
        'monto' => 120.0,
        'metodo' => PaymentMethod::EFECTIVO,
        'estado' => PaymentStatus::VERIFICADO,
        'verificado_por' => $this->admin->id,
        'verificado_en' => now(),
    ]);

    Livewire::test(ListPayments::class)
        ->assertCanSeeTableRecords([$payment])
        ->assertSee($studentFirstName)
        ->assertSee($studentLastName)
        ->assertSee($studentCi)
        ->assertSee('Ciberseguridad Aplicada')
        ->assertSee('Bs.')
        ->assertSee('120');
});

it('attaches SessionsRelationManager and PreinscriptionsRelationManager to GroupResource', function () {
    $relations = GroupResource::getRelations();

    expect($relations)->toContain(PreinscriptionsRelationManager::class)
        ->and($relations)->toContain(SessionsRelationManager::class);
});

it('lists group sessions inside SessionsRelationManager within the group detail view', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create(['course_id' => $course->id]);
    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => '2026-11-10',
        'hora_inicio' => '14:15',
        'hora_fin' => '15:45',
        'dictada' => false,
        'es_pospuesta' => true,
    ]);

    Livewire::test(SessionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->assertCanSeeTableRecords([$session])
        ->assertSee('10/11/2026')
        ->assertSee('14:15')
        ->assertSee('15:45');
});

it('edits a session through SessionsRelationManager without errors and validates schedule', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create(['course_id' => $course->id]);
    $session = Session::factory()->create([
        'group_id' => $group->id,
        'fecha' => '2026-11-10',
        'hora_inicio' => '14:15',
        'hora_fin' => '15:45',
        'dictada' => false,
    ]);

    Livewire::test(SessionsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => ViewGroup::class,
    ])
        ->callTableAction('edit', $session, data: [
            'fecha' => '2026-11-11',
            'hora_inicio' => '15:45',
            'hora_fin' => '17:15',
            'dictada' => true,
            'es_pospuesta' => true,
        ])
        ->assertHasNoTableActionErrors();

    expect($session->fresh()->dictada)->toBeTrue()
        ->and($session->fresh()->es_pospuesta)->toBeTrue();
});
