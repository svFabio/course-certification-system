<?php

declare(strict_types=1);

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Filament\Student\Resources\StudentCertificateResource\Pages\ListStudentCertificates;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows the student only their certificates and enables download when ready', function () {
    $student = User::factory()->create([
        'email' => 'student@umss.edu.bo',
        'password' => Hash::make('password'),
    ]);
    $student->assignRole(UserRole::STUDENT->value);

    $course = Course::factory()->create();
    $group = Group::factory()->create(['course_id' => $course->id]);

    $ownPre = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => $student->email,
    ]);
    $otherPre = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'email' => 'otro@example.com',
    ]);

    $ready = Certificate::factory()->create([
        'preinscription_id' => $ownPre->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::APROBACION,
        'signature_status' => SignatureStatus::LISTO,
        'codigo_unico' => 'CERT-2026-READY01',
    ]);
    $pending = Certificate::factory()->create([
        'preinscription_id' => $ownPre->id,
        'course_id' => $course->id,
        'tipo' => CertificateType::ASISTENCIA,
        'signature_status' => SignatureStatus::PENDIENTE,
        'codigo_unico' => 'CERT-2026-PEND01',
    ]);
    Certificate::factory()->create([
        'preinscription_id' => $otherPre->id,
        'course_id' => $course->id,
        'codigo_unico' => 'CERT-2026-OTHER01',
        'signature_status' => SignatureStatus::LISTO,
    ]);

    $this->actingAs($student);
    filament()->setCurrentPanel(filament()->getPanel('student'));

    Livewire::test(ListStudentCertificates::class)
        ->assertCanSeeTableRecords([$ready, $pending])
        ->assertCanNotSeeTableRecords(
            Certificate::where('codigo_unico', 'CERT-2026-OTHER01')->get()
        );

    $this->get(route('certificado.descargar', $ready))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('certificado.descargar', $pending))
        ->assertSessionHasErrors();
});
