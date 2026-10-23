<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Exports\CourseClosingExport;
use App\Exports\CoursesGeneralExport;
use App\Exports\FinancialReportExport;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

uses(RefreshDatabase::class);

it('exports course closing data with academic grades, passing rates, and styles', function () {
    $teacher = User::factory()->create(['name' => fake()->name()]);
    $course = Course::factory()->create([
        'nombre' => 'Curso de Cierre y Excel',
        'instructor_id' => $teacher->id,
        'carga_horaria' => '30',
        'attendance_weight' => 50,
    ]);

    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'Grupo 1']);
    Session::factory()->count(2)->create(['group_id' => $group->id]);

    $criteria = EvaluationCriteria::factory()->create([
        'course_id' => $course->id,
        'nombre' => 'Examen Final',
        'ponderacion' => 50.0,
    ]);

    $paterno = fake()->lastName();
    $materno = fake()->lastName();
    $nombres = fake()->firstName();
    $student1 = Preinscription::factory()->inscrito()->create([
        'group_id' => $group->id,
        'apellido_paterno' => $paterno,
        'apellido_materno' => $materno,
        'nombres' => $nombres,
    ]);
    Grade::factory()->create([
        'evaluation_criteria_id' => $criteria->id,
        'preinscription_id' => $student1->id,
        'nota' => 100.0,
    ]);

    $export = new CourseClosingExport($course);
    $rows = $export->array();

    expect($export->title())->toContain('Cierre - ')
        ->and($rows[0][0])->toBe('UNIVERSIDAD MAYOR DE SAN SIMÓN')
        ->and($rows[2][0])->toBe('ACTA DE CIERRE Y CALIFICACIONES DEL CURSO');

    // Headers row is at index 11
    $headerRow = $rows[11];
    expect($headerRow)->toContain('#')
        ->and($headerRow)->toContain('Apellidos y Nombres')
        ->and($headerRow)->toContain('Nota Final')
        ->and($headerRow)->toContain('Condición');

    // Check styling method
    $styles = $export->styles(new Worksheet);
    expect($styles)->toBeArray()
        ->and($styles)->toHaveKey(1)
        ->and($styles)->toHaveKey(12);
});

it('exports financial reconciliation report by course and consolidated', function () {
    $course = Course::factory()->create(['nombre' => 'Finanzas Curso']);
    $group = Group::factory()->create(['course_id' => $course->id, 'nombre' => 'G1']);

    $pre1 = Preinscription::factory()->inscrito()->create(['group_id' => $group->id]);
    $pre2 = Preinscription::factory()->create([
        'group_id' => $group->id,
        'status' => PreinscriptionStatus::REEMBOLSADO,
    ]);

    Payment::factory()->create([
        'preinscription_id' => $pre1->id,
        'metodo' => PaymentMethod::EFECTIVO,
        'estado' => PaymentStatus::VERIFICADO,
        'monto' => 200.0,
    ]);

    Payment::factory()->create([
        'preinscription_id' => $pre1->id,
        'metodo' => PaymentMethod::QR,
        'estado' => PaymentStatus::VERIFICADO,
        'monto' => 150.0,
    ]);

    Payment::factory()->create([
        'preinscription_id' => $pre2->id,
        'metodo' => PaymentMethod::EFECTIVO,
        'estado' => PaymentStatus::REEMBOLSADO,
        'monto' => 200.0,
    ]);

    // Test course-scoped export
    $courseExport = new FinancialReportExport($course);
    $courseRows = $courseExport->array();

    expect($courseExport->title())->toContain('Financiero - ')
        ->and($courseRows[0][0])->toBe('UNIVERSIDAD MAYOR DE SAN SIMÓN')
        ->and($courseRows[4][1])->toBe('Finanzas Curso');

    // Group row is at index 8
    $groupData = $courseRows[8];
    expect($groupData[0])->toBe('Finanzas Curso')
        ->and($groupData[1])->toBe('G1')
        ->and($groupData[2])->toBe(1) // 1 alumno inscrito
        ->and($groupData[3])->toBe('350.00') // 200 + 150 recaudado
        ->and($groupData[4])->toBe('200.00') // efectivo
        ->and($groupData[5])->toBe('150.00') // qr
        ->and($groupData[6])->toBe('200.00') // reembolsado
        ->and($groupData[7])->toBe('150.00'); // neto (350 - 200)

    // Test general consolidated export
    $generalExport = new FinancialReportExport(null);
    $generalRows = $generalExport->array();
    expect($generalExport->title())->toBe('Reporte Financiero General');

    $totalRow = collect($generalRows)->firstWhere(0, 'TOTAL GENERAL');
    expect($totalRow[0])->toBe('TOTAL GENERAL')
        ->and($totalRow[3])->toBe('350.00')
        ->and($totalRow[7])->toBe('150.00');

    // HU-19 Criteria 2: 20% facultad, 80% docente/lab
    $facultyRow = collect($generalRows)->first(fn ($r) => str_contains($r[0] ?? '', 'Retención Facultativa'));
    $docenteRow = collect($generalRows)->first(fn ($r) => str_contains($r[0] ?? '', 'Presupuesto Laboratorio'));
    expect($facultyRow[7])->toBe('30.00') // 150 * 0.20
        ->and($docenteRow[7])->toBe('120.00'); // 150 * 0.80

    // Check style keys
    $styles = $generalExport->styles(new Worksheet);
    expect($styles)->toHaveKey(8);
});

it('exports general course catalog with filters and styles', function () {
    Course::factory()->create([
        'nombre' => 'Laravel Avanzado',
        'carga_horaria' => '30',
    ]);
    Course::factory()->create([
        'nombre' => 'Docker y DevOps',
        'carga_horaria' => '20',
    ]);

    $export = new CoursesGeneralExport(['carga_horaria' => '30']);
    $rows = $export->array();

    expect($export->title())->toBe('Catálogo de Cursos')
        ->and($rows[0][0])->toBe('UNIVERSIDAD MAYOR DE SAN SIMÓN');

    // Course row is at index 6
    expect(count($rows))->toBe(7) // 5 header rows + 1 table header + 1 course row
        ->and($rows[6][1])->toBe('Laravel Avanzado');

    $styles = $export->styles(new Worksheet);
    expect($styles)->toBeArray()
        ->and($styles)->toHaveKey(1)
        ->and($styles)->toHaveKey(6);
});
