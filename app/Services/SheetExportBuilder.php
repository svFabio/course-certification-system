<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Support\Collection;

class SheetExportBuilder
{
    private Group $group;

    public function __construct(Group $group)
    {
        $this->group = $group->load([
            'course.instructor',
            'course.evaluationCriteria',
            'sessions' => fn ($q) => $q->orderBy('fecha'),
            'preinscriptions.payments',
            'preinscriptions.attendances',
            'preinscriptions.grades.evaluationCriteria',
        ]);
    }

    public function getCashSheetTabName(): string
    {
        return 'CAJA-'.$this->group->nombre;
    }

    public function getTeacherSheetTabName(): string
    {
        return 'NOTAS-'.$this->group->nombre;
    }

    public function buildCashSheetRows(): array
    {
        $course = $this->group->course;
        $instructor = $course->instructor;

        $rows = [];
        $rows[] = ['PLANILLA DE CAJA / INSCRIPCIÓN'];
        $rows[] = ['Curso: '.$course->nombre, '', 'Período: '.$course->periodo];
        $rows[] = ['Instructor: '.$instructor->name, '', 'Horario: '.$this->group->hora_inicio->format('H:i').' - '.$this->group->hora_fin->format('H:i')];
        $rows[] = [
            'Precio UMSS: '.$course->precio_umss,
            '',
            'Precio Externo: '.$course->precio_externo,
            '',
            'Precio Auxiliar: '.$course->precio_auxiliar,
        ];
        $rows[] = [];
        $rows[] = ['Nro', 'COD-SIS', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES', 'CELULAR', 'MONTO FISICO', 'MONTO QR', 'OBS', 'FOTOCOPIA C.I.'];

        $preinscriptions = $this->getCashPreinscriptions();
        $nro = 1;

        foreach ($preinscriptions as $preinscription) {
            $rows[] = [
                $nro++,
                $preinscription->cod_sis ?? '',
                $preinscription->ci,
                $preinscription->apellido_paterno,
                $preinscription->apellido_materno,
                $preinscription->nombres,
                $preinscription->celular,
                $this->sumVerifiedPayments($preinscription, PaymentMethod::EFECTIVO),
                $this->sumVerifiedPayments($preinscription, PaymentMethod::QR),
                '',
                $preinscription->fotocopia_ci ? 'SÍ' : 'NO',
            ];
        }

        return $rows;
    }

    public function buildTeacherSheetRows(): array
    {
        $course = $this->group->course;
        $instructor = $course->instructor;
        $sessions = $this->group->sessions;
        $criteria = $course->evaluationCriteria;

        $rows = [];
        $rows[] = ['PLANILLA DOCENTE - NOTAS Y ASISTENCIA'];
        $rows[] = ['Curso: '.$course->nombre, '', 'Aula: '.($this->group->aula ?? ''), '', 'Período: '.$course->periodo];
        $rows[] = ['Instructor: '.$instructor->name, '', 'Horario: '.$this->group->hora_inicio->format('H:i').' - '.$this->group->hora_fin->format('H:i')];
        $rows[] = [];

        $sessionCount = $sessions->count();
        $attendanceWeight = (int) ($course->attendance_weight ?? BusinessRules::DEFAULT_ATTENDANCE_WEIGHT);

        // Columns layout (0-indexed): A-E = identity (5 cols), then sessions, then Asistencia, Puntaje, criteria, NOTA FINAL
        $headerRow5 = array_merge(
            ['Nro', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES'],
            $sessions->map(fn ($s) => $s->fecha->format('d/m'))->toArray(),
            ['Asistencia', "Puntaje /{$attendanceWeight}"],
            $criteria->pluck('nombre')->toArray(),
            ['NOTA FINAL']
        );
        $rows[] = $headerRow5;

        $headerRow6 = array_merge(
            ['', '', '', '', ''],
            $sessions->pluck('fecha')->map(fn () => '')->toArray(),
            ['', "{$attendanceWeight}%"],
            $criteria->pluck('ponderacion')->map(fn ($p) => $p.'%')->toArray(),
            ['100']
        );
        $rows[] = $headerRow6;

        $preinscriptions = $this->getTeacherPreinscriptions();
        $nro = 1;

        // Row 1-4: header info, Row 5: column headers, Row 6: weights => data starts at Row 7
        $dataStartRow = 7;

        foreach ($preinscriptions as $index => $preinscription) {
            $currentRow = $dataStartRow + $index;
            $attendanceMarks = $this->buildAttendanceMarks($preinscription, $sessions);

            // Attendance marks: columns F through (F + sessionCount - 1), i.e. index 5...(5+sessionCount-1)
            $firstAttendanceCol = $this->columnLetter(5);
            $lastAttendanceCol = $this->columnLetter(5 + $sessionCount - 1);

            // Asistencia = count of 1s (PRESENTE or JUSTIFICADO)
            $asistenciaCol = $this->columnLetter(5 + $sessionCount);

            $asistenciaFormula = $sessionCount > 0
                ? "=COUNTIF({$firstAttendanceCol}{$currentRow}:{$lastAttendanceCol}{$currentRow};1)"
                : '0';
            $puntajeFormula = $sessionCount > 0
                ? "=ROUND(({$asistenciaCol}{$currentRow}/{$sessionCount})*{$attendanceWeight};2)"
                : '0';

            $gradeStartIndex = 5 + $sessionCount + 2;
            $puntajeCol = $this->columnLetter(5 + $sessionCount + 1);
            $notaFinalFormula = $this->buildWeightedFinalGradeFormula($criteria, $gradeStartIndex, $currentRow, "{$puntajeCol}{$currentRow}");

            $grades = $this->buildGradesRow($preinscription, $criteria);

            $rows[] = array_merge(
                [
                    $nro++,
                    $preinscription->ci,
                    $preinscription->apellido_paterno,
                    $preinscription->apellido_materno,
                    $preinscription->nombres,
                ],
                $attendanceMarks,
                [$asistenciaFormula, $puntajeFormula],
                $grades,
                [$notaFinalFormula]
            );
        }

        return $rows;
    }

    private function getCashPreinscriptions(): Collection
    {
        return $this->group->preinscriptions->filter(fn (Preinscription $p) => in_array($p->status, [
            PreinscriptionStatus::INSCRITO,
            PreinscriptionStatus::PENDIENTE_PAGO,
        ]))->values();
    }

    private function getTeacherPreinscriptions(): Collection
    {
        return $this->group->preinscriptions
            ->filter(fn (Preinscription $p) => $p->status === PreinscriptionStatus::INSCRITO)
            ->values();
    }

    private function sumVerifiedPayments(Preinscription $preinscription, PaymentMethod $method): float
    {
        return (float) $preinscription->payments
            ->filter(fn ($p) => $p->metodo === $method && $p->estado === PaymentStatus::VERIFICADO)
            ->sum('monto');
    }

    private function buildAttendanceMarks(Preinscription $preinscription, Collection $sessions): array
    {
        $attendanceMap = $preinscription->attendances->keyBy('session_id');

        return $sessions->map(function (mixed $session) use ($attendanceMap) {
            $attendance = $attendanceMap->get($session->id);

            return match ($attendance?->status) {
                AttendanceStatus::PRESENTE => 1,
                AttendanceStatus::JUSTIFICADO => 1,
                AttendanceStatus::AUSENTE => 0,
                default => '',
            };
        })->toArray();
    }

    private function buildGradesRow(Preinscription $preinscription, Collection $criteria): array
    {
        $gradesMap = $preinscription->grades->keyBy('evaluation_criteria_id');

        return $criteria->map(function (mixed $criterion) use ($gradesMap) {
            $grade = $gradesMap->get($criterion->id);

            return $grade?->nota ?? '';
        })->toArray();
    }

    /**
     * NOTA FINAL = puntaje_asistencia + sum(nota * ponderacion / 100) — same formula as EvaluationService.
     * Uses ';' as argument separator for Spanish/Es Google Sheets locales.
     */
    private function buildWeightedFinalGradeFormula(Collection $criteria, int $gradeStartIndex, int $currentRow, string $attendanceScoreCell): string
    {
        $terms = $criteria->map(function (mixed $criterion, int $index) use ($gradeStartIndex, $currentRow): string {
            $col = $this->columnLetter($gradeStartIndex + $index);

            return "{$col}{$currentRow}*{$criterion->ponderacion}/100";
        })->implode('+');

        if ($terms === '') {
            return "=ROUND({$attendanceScoreCell};2)";
        }

        return "=ROUND({$attendanceScoreCell}+{$terms};2)";
    }

    /**
     * Convert a 0-indexed column number to a Google Sheets column letter (A, B, ..., Z, AA, AB, ...).
     */
    private function columnLetter(int $index): string
    {
        $letter = '';
        $n = $index;

        while ($n >= 0) {
            $letter = chr($n % 26 + 65).$letter;
            $n = intdiv($n, 26) - 1;
        }

        return $letter;
    }
}
