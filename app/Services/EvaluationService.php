<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for the configurable-weight grading system.
 *
 * NOTA FINAL = attendance_score + Σ(nota_i × ponderacion_i / 100), on a 0–100 scale.
 * Invariant enforced on every write: attendance_weight + Σ(criteria.ponderacion) === 100.
 */
class EvaluationService
{
    /**
     * Σ ponderacion of the course criteria (fresh query, never cached).
     */
    public function criteriaSum(Course $course): int
    {
        return (int) round((float) $course->evaluationCriteria()->sum('ponderacion'));
    }

    /**
     * Enforce attendance_weight + criteriaSum === 100%, showing the actual numbers.
     *
     * @throws ValidationException
     */
    public function assertWeightsSumTo100(int $attendanceWeight, int $criteriaSum): void
    {
        $total = $attendanceWeight + $criteriaSum;

        if ($total !== BusinessRules::EVALUATION_TOTAL_PERCENT) {
            throw ValidationException::withMessages([
                'weights' => "La ponderación total debe ser 100% (asistencia {$attendanceWeight}% + criterios {$criteriaSum}% = {$total}%). Ajusta los porcentajes.",
            ]);
        }
    }

    /**
     * Set the course attendance weight, validating range and the combined sum.
     *
     * @throws ValidationException
     */
    public function setAttendanceWeight(Course $course, int $weight): void
    {
        $this->assertAttendanceWeightInRange($weight);
        $this->assertWeightsSumTo100($weight, $this->criteriaSum($course));

        $course->attendance_weight = $weight;
        $course->save();
    }

    /**
     * Replace the course criteria in bulk, enforcing the combined sum invariant.
     *
     * @param  array<int, array{nombre: string, ponderacion: int|float}>  $criteria
     *
     * @throws ValidationException
     */
    public function setCriteria(Course $course, array $criteria): void
    {
        $totalWeight = (int) round(array_sum(array_column($criteria, 'ponderacion')));

        $this->assertWeightsSumTo100($this->attendanceWeight($course), $totalWeight);

        DB::transaction(function () use ($course, $criteria) {
            $course->evaluationCriteria()->delete();

            foreach ($criteria as $item) {
                EvaluationCriteria::create([
                    'course_id' => $course->id,
                    'nombre' => $item['nombre'],
                    'ponderacion' => $item['ponderacion'],
                ]);
            }
        });
    }

    /**
     * Add a criterion, enforcing the combined sum invariant before creating it.
     *
     * @throws ValidationException
     */
    public function addCriteria(Course $course, string $nombre, int $ponderacion): EvaluationCriteria
    {
        $this->assertWeightsSumTo100(
            $this->attendanceWeight($course),
            $this->criteriaSum($course) + $ponderacion,
        );

        return EvaluationCriteria::create([
            'course_id' => $course->id,
            'nombre' => $nombre,
            'ponderacion' => $ponderacion,
        ]);
    }

    /**
     * Update a criterion, enforcing the combined sum with the old weight
     * excluded and the new weight included. Optionally move it to another course;
     * in that case both the origin and the target must remain balanced.
     *
     * @throws ValidationException
     */
    public function updateCriteria(EvaluationCriteria $criterion, string $nombre, int $ponderacion, ?Course $course = null): void
    {
        $target = $course ?? $criterion->course;
        $oldWeight = (int) round((float) $criterion->ponderacion);

        if ($target->is($criterion->course)) {
            $this->assertWeightsSumTo100(
                $this->attendanceWeight($target),
                $this->criteriaSum($target) - $oldWeight + $ponderacion,
            );
        } else {
            $this->assertWeightsSumTo100(
                $this->attendanceWeight($criterion->course),
                $this->criteriaSum($criterion->course) - $oldWeight,
            );
            $this->assertWeightsSumTo100(
                $this->attendanceWeight($target),
                $this->criteriaSum($target) + $ponderacion,
            );
        }

        $criterion->update([
            'nombre' => $nombre,
            'ponderacion' => $ponderacion,
            'course_id' => $target->id,
        ]);
    }

    /**
     * Remove a criterion only if the remaining weights stay balanced.
     * Dependent grades are removed by the FK cascade on grades.evaluation_criteria_id.
     *
     * @throws ValidationException
     */
    public function removeCriteria(EvaluationCriteria $criterion): void
    {
        $course = $criterion->course;

        $this->assertWeightsSumTo100(
            $this->attendanceWeight($course),
            $this->criteriaSum($course) - (int) round((float) $criterion->ponderacion),
        );

        $criterion->delete();
    }

    /**
     * attendance_score = (sessions attended / total sessions) × attendance_weight.
     * PRESENTE and JUSTIFICADO both count as attended. Zero sessions → 0.0.
     *
     * For batch callers, eager-load `group.course`, `group.sessions` and
     * `attendances` on the preinscriptions to avoid N+1 queries; single-student
     * callers can rely on lazy loading.
     */
    public function attendanceScore(Preinscription $preinscription): float
    {
        $sessionCount = $preinscription->group->sessions->count();

        if ($sessionCount === 0) {
            return 0.0;
        }

        $attended = $this->attendedSessionsCount($preinscription);
        $weight = $this->attendanceWeight($preinscription->group->course);

        return round(($attended / $sessionCount) * $weight, 2);
    }

    /**
     * NOTA FINAL = attendance_score + Σ(nota × ponderacion / 100), range 0–100.
     * Unified formula behind certificates, teacher-planilla exports and the
     * (future) instructor matrix.
     */
    public function finalGrade(Preinscription $preinscription): float
    {
        $grades = $preinscription->grades()->with('evaluationCriteria')->get();

        $criteriaTotal = $grades->sum(
            fn (Grade $grade) => ($grade->nota * (float) $grade->evaluationCriteria->ponderacion) / 100
        );

        return round($this->attendanceScore($preinscription) + $criteriaTotal, 2);
    }

    /**
     * Full grade breakdown: attendance entry (weight/score/weighted),
     * per-criterion entries, and the totals.
     *
     * @return array{
     *     preinscription_id: int,
     *     full_name: string,
     *     attendance: array{weight: int, score: float, weighted: float},
     *     criteria: array<int, array{name: string, weight: float, score: float, weighted: float}>,
     *     total: float,
     *     final_grade: float,
     *     passed: bool
     * }
     */
    public function breakdown(Preinscription $preinscription): array
    {
        $sessionCount = $preinscription->group->sessions->count();
        $attended = $this->attendedSessionsCount($preinscription);
        $attendanceWeight = $this->attendanceWeight($preinscription->group->course);
        $attendancePercent = $sessionCount > 0
            ? round(($attended / $sessionCount) * 100, 2)
            : 0.0;
        $attendanceWeighted = round(($attendancePercent / 100) * $attendanceWeight, 2);

        $grades = $preinscription->grades()->with('evaluationCriteria')->get();
        $criteria = $grades->map(fn (Grade $grade) => [
            'name' => $grade->evaluationCriteria->nombre,
            'weight' => $grade->evaluationCriteria->ponderacion,
            'score' => $grade->nota,
            'weighted' => round(($grade->nota * (float) $grade->evaluationCriteria->ponderacion) / 100, 2),
        ])->values()->toArray();

        $criteriaTotal = round(array_sum(array_column($criteria, 'weighted')), 2);
        $total = round($attendanceWeighted + $criteriaTotal, 2);

        return [
            'preinscription_id' => $preinscription->id,
            'full_name' => $preinscription->full_name,
            'attendance' => [
                'weight' => $attendanceWeight,
                'score' => $attendancePercent,
                'weighted' => $attendanceWeighted,
            ],
            'criteria' => $criteria,
            'total' => $total,
            'final_grade' => $total,
            'passed' => $total >= BusinessRules::MINIMUM_PASSING_GRADE,
        ];
    }

    /**
     * Atomically apply a staged evaluation configuration coming from the
     * instructor grade matrix: attendance weight, criteria operations
     * (keep/add/update/delete) and grade writes.
     *
     * Everything is validated BEFORE touching the database; all mutations run
     * inside a single transaction, so a failure never leaves a partial state.
     *
     * @param  array<int, array{id: int|null, nombre: string, ponderacion: int|float, op: 'keep'|'add'|'update'|'delete'}>  $criteriaOps
     *                                                                                                                                    Staged criteria. `id` is the real criterion id for keep/update/delete
     *                                                                                                                                    entries and the staging key (a negative temp id) for 'add' entries.
     * @param  array<int, array<int, float|string>>  $gradeWrites
     *                                                             gradeWrites[preinscriptionId][criterionIdOrStagingKey] = nota.
     *                                                             Writes resolving to deleted (or foreign) criteria are skipped.
     *
     * @throws ValidationException
     */
    public function applyEvaluationConfig(Course $course, int $attendanceWeight, array $criteriaOps, array $gradeWrites): void
    {
        $this->assertAttendanceWeightInRange($attendanceWeight);

        $criteriaSum = 0;

        foreach ($criteriaOps as $index => $operation) {
            $op = (string) ($operation['op'] ?? 'keep');
            $id = $operation['id'] ?? null;
            $nombre = trim((string) ($operation['nombre'] ?? ''));
            $ponderacion = $operation['ponderacion'] ?? null;

            if (! in_array($op, ['keep', 'add', 'update', 'delete'], true)) {
                throw ValidationException::withMessages([
                    'criteria' => 'Operación de criterio no válida.',
                ]);
            }

            if ($id === null || ! is_int($id)) {
                throw ValidationException::withMessages([
                    'criteria' => 'Cada criterio debe incluir un identificador válido.',
                ]);
            }

            if ($op === 'delete') {
                continue;
            }

            if ($nombre === '') {
                throw ValidationException::withMessages([
                    'criteria' => 'La evaluación #'.($index + 1).' debe tener un nombre.',
                ]);
            }

            if (! is_numeric($ponderacion)
                || (float) $ponderacion !== (float) (int) (float) $ponderacion
                || (int) (float) $ponderacion < 1
                || (int) (float) $ponderacion > BusinessRules::EVALUATION_TOTAL_PERCENT) {
                throw ValidationException::withMessages([
                    'criteria' => "La ponderación de «{$nombre}» debe ser un valor numérico entero entre 1 y 100.",
                ]);
            }

            $criteriaSum += (int) (float) $ponderacion;
        }

        $this->assertWeightsSumTo100($attendanceWeight, $criteriaSum);

        foreach ($gradeWrites as $preinscriptionId => $notes) {
            foreach ($notes as $criteriaKey => $nota) {
                if (! is_numeric($nota)
                    || (float) $nota < BusinessRules::GRADE_MIN
                    || (float) $nota > BusinessRules::GRADE_MAX) {
                    throw ValidationException::withMessages([
                        'grades' => "La nota {$nota} del participante #{$preinscriptionId} está fuera del rango 0 a 100.",
                    ]);
                }
            }
        }

        DB::transaction(function () use ($course, $attendanceWeight, $criteriaOps, $gradeWrites): void {
            $course->attendance_weight = $attendanceWeight;
            $course->save();

            $stagingMap = [];

            foreach ($criteriaOps as $operation) {
                if ($operation['op'] !== 'add') {
                    continue;
                }

                $criterion = EvaluationCriteria::create([
                    'course_id' => $course->id,
                    'nombre' => trim((string) $operation['nombre']),
                    'ponderacion' => (int) (float) $operation['ponderacion'],
                ]);

                $stagingMap[(int) $operation['id']] = $criterion->id;
            }

            foreach ($criteriaOps as $operation) {
                if ($operation['op'] !== 'update') {
                    continue;
                }

                EvaluationCriteria::query()
                    ->where('course_id', $course->id)
                    ->whereKey($operation['id'])
                    ->update([
                        'nombre' => trim((string) $operation['nombre']),
                        'ponderacion' => (int) (float) $operation['ponderacion'],
                    ]);
            }

            foreach ($criteriaOps as $operation) {
                if ($operation['op'] !== 'delete') {
                    continue;
                }

                $criterion = EvaluationCriteria::query()
                    ->where('course_id', $course->id)
                    ->whereKey($operation['id'])
                    ->first();

                if ($criterion === null) {
                    continue;
                }

                Grade::query()
                    ->where('evaluation_criteria_id', $criterion->id)
                    ->delete();
                $criterion->delete();
            }

            $validCriteriaIds = EvaluationCriteria::query()
                ->where('course_id', $course->id)
                ->pluck('id')
                ->flip();

            foreach ($gradeWrites as $preinscriptionId => $notes) {
                foreach ($notes as $criteriaKey => $nota) {
                    $criteriaId = $stagingMap[(int) $criteriaKey] ?? (int) $criteriaKey;

                    if (! isset($validCriteriaIds[$criteriaId])) {
                        continue;
                    }

                    Grade::updateOrCreate(
                        [
                            'preinscription_id' => (int) $preinscriptionId,
                            'evaluation_criteria_id' => $criteriaId,
                        ],
                        ['nota' => (float) $nota],
                    );
                }
            }
        });
    }

    public function recordGrades(Preinscription $preinscription, array $grades): void
    {
        DB::transaction(function () use ($preinscription, $grades) {
            foreach ($grades as $criteriaId => $score) {
                Grade::updateOrCreate(
                    [
                        'preinscription_id' => $preinscription->id,
                        'evaluation_criteria_id' => $criteriaId,
                    ],
                    ['nota' => $score]
                );
            }
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertAttendanceWeightInRange(int $weight): void
    {
        if ($weight < 0 || $weight > BusinessRules::EVALUATION_TOTAL_PERCENT) {
            throw ValidationException::withMessages([
                'attendance_weight' => 'El peso de asistencia debe estar entre 0% y 100%.',
            ]);
        }
    }

    /**
     * In-memory models created without the column (e.g. factories) fall back to
     * the domain default, which matches the database default.
     */
    private function attendanceWeight(Course $course): int
    {
        return (int) ($course->attendance_weight ?? BusinessRules::DEFAULT_ATTENDANCE_WEIGHT);
    }

    private function attendedSessionsCount(Preinscription $preinscription): int
    {
        return $preinscription->attendances
            ->filter(fn (Attendance $attendance) => in_array($attendance->status, [
                AttendanceStatus::PRESENTE,
                AttendanceStatus::JUSTIFICADO,
            ], true))
            ->count();
    }
}
