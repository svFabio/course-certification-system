<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
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

        $headerRow5 = array_merge(
            ['Nro', 'CI', 'APE_PAT', 'APE_MAT', 'NOMBRES'],
            $sessions->map(fn ($s) => $s->fecha->format('d/m'))->toArray(),
            ['TOTAL ASIST'],
            $criteria->pluck('nombre')->toArray(),
            ['NOTA FINAL']
        );
        $rows[] = $headerRow5;

        $headerRow6 = array_merge(
            ['', '', '', '', ''],
            $sessions->pluck('fecha')->map(fn () => '')->toArray(),
            ['50%'],
            $criteria->pluck('ponderacion')->map(fn ($p) => $p.'%')->toArray(),
            ['']
        );
        $rows[] = $headerRow6;

        $preinscriptions = $this->getTeacherPreinscriptions();
        $sessionsCount = $sessions->count();
        $nro = 1;

        foreach ($preinscriptions as $preinscription) {
            $attendanceMarks = $this->buildAttendanceMarks($preinscription, $sessions);
            $attendanceTotal = $this->calculateAttendanceTotal($preinscription, $sessionsCount);
            $grades = $this->buildGradesRow($preinscription, $criteria);
            $finalGrade = $attendanceTotal + array_sum($grades);

            $rows[] = array_merge(
                [
                    $nro++,
                    $preinscription->ci,
                    $preinscription->apellido_paterno,
                    $preinscription->apellido_materno,
                    $preinscription->nombres,
                ],
                $attendanceMarks,
                [$attendanceTotal],
                $grades,
                [round($finalGrade, 2)]
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

    private function calculateAttendanceTotal(Preinscription $preinscription, int $sessionsCount): float
    {
        if ($sessionsCount === 0) {
            return 0.0;
        }

        $presentCount = $preinscription->attendances
            ->filter(fn (mixed $a) => in_array($a->status, [AttendanceStatus::PRESENTE, AttendanceStatus::JUSTIFICADO]))
            ->count();

        return (float) $presentCount;
    }

    private function calculateAttendanceScore(float $attendanceTotal, int $sessionsCount): float
    {
        if ($sessionsCount === 0) {
            return 0.0;
        }

        return round(($attendanceTotal / $sessionsCount) * 50, 2);
    }

    private function buildGradesRow(Preinscription $preinscription, Collection $criteria): array
    {
        $gradesMap = $preinscription->grades->keyBy('evaluation_criteria_id');

        return $criteria->map(function (mixed $criterion) use ($gradesMap) {
            $grade = $gradesMap->get($criterion->id);

            return $grade?->nota ?? '';
        })->toArray();
    }

    public function buildCashDataRows(): array
    {
        $preinscriptions = $this->getCashPreinscriptions();
        $rows = [];
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

    public function buildTeacherDataRows(): array
    {
        $sessions = $this->group->sessions;
        $criteria = $this->group->course->evaluationCriteria;
        $sessionsCount = $sessions->count();
        $preinscriptions = $this->getTeacherPreinscriptions();
        $rows = [];
        $nro = 1;

        foreach ($preinscriptions as $preinscription) {
            $attendanceMarks = $this->buildAttendanceMarks($preinscription, $sessions);
            $attendanceTotal = $this->calculateAttendanceTotal($preinscription, $sessionsCount);
            $attendanceScore = $this->calculateAttendanceScore($attendanceTotal, $sessionsCount);
            $grades = $this->buildGradesRow($preinscription, $criteria);
            $finalGrade = $attendanceScore + array_sum($grades);

            $rows[] = array_merge(
                [
                    $nro++,
                    $preinscription->ci,
                    $preinscription->apellido_paterno,
                    $preinscription->apellido_materno,
                    $preinscription->nombres,
                ],
                $attendanceMarks,
                [$attendanceTotal],
                [$attendanceScore],
                $grades,
                [round($finalGrade, 2)]
            );
        }

        return $rows;
    }
}
