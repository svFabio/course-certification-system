<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::ADMIN->value);
});

it('searches admin attendance table by participant name without SQL error', function () {
    $this->actingAs($this->admin);

    $course = Course::factory()->withHours('20')->create();
    $group = Group::factory()->habilitado()->create(['course_id' => $course->id]);
    $session = Session::factory()->create(['group_id' => $group->id]);
    $preinscription = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'nombres' => 'Zoila',
        'apellido_paterno' => 'Mamani',
    ]);
    Attendance::create([
        'session_id' => $session->id,
        'preinscription_id' => $preinscription->id,
        'status' => AttendanceStatus::PRESENTE,
    ]);

    $attendance = Attendance::where('preinscription_id', $preinscription->id)->firstOrFail();

    $component = Livewire::test(ListAttendances::class)
        ->searchTable('Zoila');

    $component->assertCanSeeTableRecords(collect([$attendance]));
});
