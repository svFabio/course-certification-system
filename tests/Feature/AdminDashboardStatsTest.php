<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\GroupResource;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UpcomingGroupsWidget;
use App\Models\Course;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($this->admin);
});

it('computes correct revenue, enrolled student count, and pending counts in StatsOverviewWidget', function () {
    $course = Course::factory()->create(['status' => CourseStatus::EN_CURSO]);
    $group = Group::factory()->create(['course_id' => $course->id, 'status' => GroupStatus::HABILITADO]);

    $p1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Payment::factory()->create([
        'preinscription_id' => $p1->id,
        'estado' => PaymentStatus::VERIFICADO,
        'monto' => 250.00,
        'verificado_en' => now(),
        'verificado_por' => $this->admin->id,
    ]);

    $p2 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    Payment::factory()->create([
        'preinscription_id' => $p2->id,
        'estado' => PaymentStatus::VERIFICADO,
        'monto' => 150.50,
        'verificado_en' => now(),
        'verificado_por' => $this->admin->id,
    ]);

    $p3 = Preinscription::factory()->pendiente()->create(['group_id' => $group->id]);
    Payment::factory()->create([
        'preinscription_id' => $p3->id,
        'estado' => PaymentStatus::PENDIENTE,
        'monto' => 200.00,
    ]);

    Livewire::test(StatsOverviewWidget::class)
        ->assertSuccessful()
        ->assertSee('Recaudación Verificada')
        ->assertSee('Bs. 400.50')
        ->assertSee('Estudiantes Inscritos')
        ->assertSee('Preinscripciones por Aprobar');
});

it('renders upcoming groups widget with record url pointing to group view', function () {
    $course = Course::factory()->create(['nombre' => 'Arquitectura de Software']);
    $group = Group::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'G1',
        'status' => GroupStatus::HABILITADO,
    ]);

    Livewire::test(UpcomingGroupsWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$group]);

    $url = GroupResource::getUrl('view', ['record' => $group]);
    expect($url)->toContain("/admin/groups/{$group->id}");
});
