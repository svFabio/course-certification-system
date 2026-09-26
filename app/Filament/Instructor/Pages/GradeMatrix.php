<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\UserRole;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\EvaluationCriteria;
use App\Models\Grade;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\EvaluationService;
use App\Support\BusinessRules;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

/**
 * Free-edit grade matrix: every change (columns, weights, notes) is staged
 * client-side and persisted atomically by saveAll() after a single validation
 * pass. Live previews are recomputed from the staged state without touching
 * the database.
 */
class GradeMatrix extends Page
{
    protected static string $view = 'filament.instructor.pages.grade-matrix';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Calificaciones';

    protected static ?string $navigationLabel = 'Calificaciones';

    public ?int $selectedCourseId = null;

    public ?int $selectedGroupId = null;

    /** Last course selection actually applied (used to revert blocked switches). */
    public ?int $appliedCourseId = null;

    /** Last group selection actually applied (used to revert blocked switches). */
    public ?int $appliedGroupId = null;

    /** @var array<int, string> */
    public array $courseOptions = [];

    /** @var array<int, string> */
    public array $groupOptions = [];

    /** Staged attendance weight; persisted only by saveAll(). */
    public int|string $attendanceWeightInput = BusinessRules::DEFAULT_ATTENDANCE_WEIGHT;

    /** Attendance weight stored in the database; dirty-state baseline. */
    public ?int $dbAttendanceWeight = null;

    /**
     * Staged criteria entries. Deleted entries become tombstones (op=delete)
     * and are hidden from rendering.
     *
     * @var array<int, array{id: int, nombre: string, ponderacion: float, ponderacion_label: string, op: 'keep'|'add'|'update'|'delete'}>
     */
    public array $criteria = [];

    /**
     * Staged notes overlay: [preinscriptionId:criterionKey] => raw string value.
     * Criterion keys are real ids for persisted criteria and negative staging
     * ids for op=add entries. Cleared cells remove the entry (fall back to the
     * stored grade). Nothing here is persisted until saveAll().
     *
     * @var array<string, string>
     */
    public array $pendingNotas = [];

    /**
     * Precomputed matrix rows: the Blade view renders these arrays only and
     * never touches the database. attendance_score / final_grade / passed are
     * recomputed from the staged state on every staged change.
     *
     * @var array<int, array{id: int, ci: string, full_name: string, attendance_pct: float, attendance_attended: int, attendance_score: float, notas: array<int, float>, final_grade: float, passed: bool}>
     */
    public array $rows = [];

    public int $sessionCount = 0;

    public ?string $lastError = null;

    public bool $criteriaModalOpen = false;

    public ?int $editingCriteriaId = null;

    public string $criteriaNombre = '';

    public int|string $criteriaPonderacion = '';

    public ?string $criteriaError = null;

    public bool $switchConfirmOpen = false;

    public bool $pendingCourseSwitch = false;

    public bool $pendingGroupSwitch = false;

    public ?int $pendingCourseId = null;

    public ?int $pendingGroupId = null;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function getHeading(): string
    {
        return 'Matriz de Calificaciones por Grupo';
    }

    public function mount(): void
    {
        $this->courseOptions = $this->buildCourseOptions();

        if (count($this->courseOptions) === 1) {
            $this->selectedCourseId = (int) array_key_first($this->courseOptions);
        }

        $this->appliedCourseId = $this->selectedCourseId;

        $this->groupOptions = $this->buildGroupOptions();

        if (count($this->groupOptions) === 1) {
            $this->selectedGroupId = (int) array_key_first($this->groupOptions);
        }

        $this->appliedGroupId = $this->selectedGroupId;

        $this->syncAttendanceWeightInput();

        if ($this->selectedGroupId !== null) {
            $this->loadMatrix();
        }
    }

    public function updatedSelectedCourseId(): void
    {
        if ($this->hasUnsavedChanges()) {
            $this->pendingCourseSwitch = true;
            $this->pendingGroupSwitch = false;
            $this->pendingCourseId = $this->selectedCourseId;
            $this->pendingGroupId = null;
            $this->selectedCourseId = $this->appliedCourseId;
            $this->switchConfirmOpen = true;

            return;
        }

        $this->commitCourseSelection();
    }

    public function updatedSelectedGroupId(): void
    {
        if ($this->hasUnsavedChanges()) {
            $this->pendingGroupSwitch = true;
            $this->pendingCourseSwitch = false;
            $this->pendingGroupId = $this->selectedGroupId;
            $this->pendingCourseId = null;
            $this->selectedGroupId = $this->appliedGroupId;
            $this->switchConfirmOpen = true;

            return;
        }

        $this->appliedGroupId = $this->selectedGroupId;
        $this->loadMatrix();
    }

    public function updatedAttendanceWeightInput(): void
    {
        $this->recomputeRows();
    }

    /** Pending switch accepted: drop staged changes and apply the snapshot target. */
    public function confirmSwitch(): void
    {
        $courseSwitch = $this->pendingCourseSwitch;
        $groupSwitch = $this->pendingGroupSwitch;
        $targetCourseId = $this->pendingCourseId;
        $targetGroupId = $this->pendingGroupId;

        $this->switchConfirmOpen = false;
        $this->pendingCourseSwitch = false;
        $this->pendingGroupSwitch = false;
        $this->pendingCourseId = null;
        $this->pendingGroupId = null;
        $this->pendingNotas = [];

        if ($courseSwitch) {
            $this->selectedCourseId = $targetCourseId;
            $this->commitCourseSelection();

            return;
        }

        $this->syncAttendanceWeightInput();

        if ($groupSwitch) {
            $this->selectedGroupId = $targetGroupId;
            $this->appliedGroupId = $targetGroupId;
            $this->loadMatrix();
        }
    }

    /** Pending switch rejected: keep the restored selection and the staged state. */
    public function cancelSwitch(): void
    {
        $this->switchConfirmOpen = false;
        $this->pendingCourseSwitch = false;
        $this->pendingGroupSwitch = false;
        $this->pendingCourseId = null;
        $this->pendingGroupId = null;
    }

    /**
     * Load the full matrix for the selected group in a fixed number of queries:
     * group + course + sessions (1), criteria (1), sessions ids (from eager
     * load), attendances for those sessions (1) and preinscriptions with
     * grades.evaluationCriteria (1 + 1). Rows are precomputed arrays; the view
     * performs zero queries. Scores are then recomputed from the staged state.
     */
    public function loadMatrix(): void
    {
        $this->resetMatrix();

        $group = $this->scopedGroup();

        if ($group === null || $this->selectedCourseId === null
            || (int) $group->course_id !== $this->selectedCourseId) {
            return;
        }

        $course = $group->course;

        $this->sessionCount = $group->sessions->count();

        $this->criteria = $course->evaluationCriteria()
            ->orderBy('id')
            ->get()
            ->map(fn (EvaluationCriteria $criterion) => [
                'id' => $criterion->id,
                'nombre' => $criterion->nombre,
                'ponderacion' => (float) $criterion->ponderacion,
                'ponderacion_label' => self::trimNumber((float) $criterion->ponderacion).'%',
                'op' => 'keep',
            ])
            ->all();

        $sessionIds = $group->sessions->pluck('id');

        $attendancesGrouped = $sessionIds->isEmpty()
            ? collect()
            : Attendance::query()
                ->whereIn('session_id', $sessionIds)
                ->get()
                ->groupBy('preinscription_id');

        $preinscriptions = $group->preinscriptions()
            ->where('status', PreinscriptionStatus::INSCRITO)
            ->with(['grades.evaluationCriteria'])
            ->get();

        foreach ($preinscriptions as $preinscription) {
            $preinscription->setRelation('group', $group);
            $preinscription->setRelation(
                'attendances',
                $attendancesGrouped->get($preinscription->id, collect())
            );

            $attended = $preinscription->attendances->filter(
                fn (Attendance $attendance) => in_array($attendance->status, [
                    AttendanceStatus::PRESENTE,
                    AttendanceStatus::JUSTIFICADO,
                ], true)
            )->count();

            $attendancePercent = $this->sessionCount > 0
                ? round(($attended / $this->sessionCount) * 100, 2)
                : 0.0;

            $notas = [];
            foreach ($preinscription->grades as $grade) {
                $notas[$grade->evaluation_criteria_id] = $grade->nota;
            }

            $this->rows[] = [
                'id' => $preinscription->id,
                'ci' => $preinscription->ci,
                'full_name' => $preinscription->full_name,
                'attendance_pct' => $attendancePercent,
                'attendance_attended' => $attended,
                'attendance_score' => 0.0,
                'notas' => $notas,
                'final_grade' => 0.0,
                'passed' => false,
            ];
        }

        $this->recomputeRows();
    }

    /**
     * Recompute every row's attendance_score, final_grade and passed badge from
     * the STAGED state (attendance weight + criteria + pending notes). No queries.
     */
    public function recomputeRows(): void
    {
        $weight = $this->stagedWeight();

        foreach ($this->rows as $index => $row) {
            $attendanceScore = round(($row['attendance_pct'] / 100) * $weight, 2);
            $criteriaTotal = 0.0;

            foreach ($this->criteria as $criterion) {
                if ($criterion['op'] === 'delete') {
                    continue;
                }

                $criteriaKey = (int) $criterion['id'];
                $pending = $this->pendingNotas[$this->notaKey((int) $row['id'], $criteriaKey)] ?? null;

                if ($pending !== null && $pending !== '') {
                    $nota = is_numeric($pending) ? (float) $pending : 0.0;
                } else {
                    $nota = (float) ($row['notas'][$criteriaKey] ?? 0.0);
                }

                $criteriaTotal += ($nota * (float) $criterion['ponderacion']) / 100;
            }

            $finalGrade = round($attendanceScore + $criteriaTotal, 2);

            $this->rows[$index]['attendance_score'] = $attendanceScore;
            $this->rows[$index]['final_grade'] = $finalGrade;
            $this->rows[$index]['passed'] = $finalGrade >= BusinessRules::MINIMUM_PASSING_GRADE;
        }
    }

    /**
     * Stage a cell value on blur/change. No validation, no persistence and no
     * notification here: everything is validated and saved by saveAll().
     * An empty value clears the pending entry (falls back to the stored grade).
     */
    public function stageNota(int $preinscriptionId, int $criteriaKey, string $nota): void
    {
        $key = $this->notaKey($preinscriptionId, $criteriaKey);

        if ($nota === '') {
            unset($this->pendingNotas[$key]);
        } else {
            $this->pendingNotas[$key] = $nota;
        }

        $this->recomputeRows();
    }

    public function notaKey(int $preinscriptionId, int $criteriaKey): string
    {
        return $preinscriptionId.':'.$criteriaKey;
    }

    /** True while any staged change differs from the database state. */
    public function hasUnsavedChanges(): bool
    {
        $dbWeight = $this->dbAttendanceWeight ?? BusinessRules::DEFAULT_ATTENDANCE_WEIGHT;

        if (! is_numeric((string) $this->attendanceWeightInput)
            || (float) $this->attendanceWeightInput !== (float) $dbWeight) {
            return true;
        }

        foreach ($this->criteria as $criterion) {
            if ($criterion['op'] !== 'keep') {
                return true;
            }
        }

        return $this->pendingNotas !== [];
    }

    /**
     * Staged attendance weight used for live previews and the Asistencia
     * header; falls back to the stored weight while the input is not numeric.
     */
    public function stagedWeight(): float
    {
        if (is_numeric((string) $this->attendanceWeightInput)) {
            return (float) $this->attendanceWeightInput;
        }

        return (float) ($this->dbAttendanceWeight ?? BusinessRules::DEFAULT_ATTENDANCE_WEIGHT);
    }

    public function stagedWeightLabel(): string
    {
        return self::trimNumber($this->stagedWeight()).'%';
    }

    public function openCreateCriteria(): void
    {
        $this->editingCriteriaId = null;
        $this->criteriaNombre = '';
        $this->criteriaPonderacion = '';
        $this->criteriaError = null;
        $this->resetValidation();
        $this->criteriaModalOpen = true;
    }

    public function openEditCriteria(int $criteriaId): void
    {
        foreach ($this->criteria as $criterion) {
            if ((int) $criterion['id'] !== $criteriaId || $criterion['op'] === 'delete') {
                continue;
            }

            $this->editingCriteriaId = (int) $criterion['id'];
            $this->criteriaNombre = (string) $criterion['nombre'];
            $this->criteriaPonderacion = (int) round((float) $criterion['ponderacion']);
            $this->criteriaError = null;
            $this->resetValidation();
            $this->criteriaModalOpen = true;

            return;
        }
    }

    public function closeCriteriaModal(): void
    {
        $this->criteriaModalOpen = false;
        $this->criteriaError = null;
        $this->resetValidation();
    }

    /**
     * Stage a criterion (create or edit). Only field-level sanity validation
     * runs here (required / max:255 / 1..100); the 100% sum invariant is
     * checked once, by saveAll().
     */
    public function saveCriteria(): void
    {
        $validated = $this->validate([
            'criteriaNombre' => ['required', 'string', 'max:255'],
            'criteriaPonderacion' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $nombre = trim((string) $validated['criteriaNombre']);
        $ponderacion = (float) (int) $validated['criteriaPonderacion'];
        $label = self::trimNumber($ponderacion).'%';

        if ($this->editingCriteriaId === null) {
            $this->criteria[] = [
                'id' => $this->nextStagingId(),
                'nombre' => $nombre,
                'ponderacion' => $ponderacion,
                'ponderacion_label' => $label,
                'op' => 'add',
            ];
        } else {
            foreach ($this->criteria as $index => $criterion) {
                if ((int) $criterion['id'] !== $this->editingCriteriaId || $criterion['op'] === 'delete') {
                    continue;
                }

                $this->criteria[$index]['nombre'] = $nombre;
                $this->criteria[$index]['ponderacion'] = $ponderacion;
                $this->criteria[$index]['ponderacion_label'] = $label;

                if ($criterion['op'] === 'keep') {
                    $this->criteria[$index]['op'] = 'update';
                }

                break;
            }
        }

        $this->criteriaError = null;
        $this->criteriaModalOpen = false;
        $this->resetValidation();
        $this->recomputeRows();
    }

    /**
     * Stage a criterion removal: entries staged as 'add' are dropped entirely,
     * persisted entries become tombstones (op=delete). No service call and no
     * validation here — saveAll() owns both.
     */
    public function removeCriteria(int $criteriaId): void
    {
        foreach ($this->criteria as $index => $criterion) {
            if ((int) $criterion['id'] !== $criteriaId) {
                continue;
            }

            if ($criterion['op'] === 'add') {
                unset($this->criteria[$index]);
                $this->criteria = array_values($this->criteria);
            } else {
                $this->criteria[$index]['op'] = 'delete';
            }

            $this->dropPendingNotasFor($criteriaId);

            break;
        }

        $this->recomputeRows();
    }

    /**
     * The single validation point: runs every rule over the staged state,
     * collects ALL errors into one banner + notification and persists nothing
     * while any error exists. On success it applies everything atomically.
     */
    public function saveAll(): void
    {
        $course = $this->scopedCourse();

        if ($course === null) {
            $this->lastError = 'Seleccione un curso para guardar los cambios.';
            Notification::make()
                ->title($this->lastError)
                ->danger()
                ->send();

            return;
        }

        $errors = [];

        $weightInput = $this->attendanceWeightInput;
        $weightOk = is_numeric((string) $weightInput)
            && (float) $weightInput === (float) (int) (float) $weightInput
            && (int) (float) $weightInput >= 0
            && (int) (float) $weightInput <= BusinessRules::EVALUATION_TOTAL_PERCENT;

        if (! $weightOk) {
            $errors[] = 'El peso de asistencia debe ser un valor numérico entre 0 y 100.';
        }

        $weight = $weightOk ? (int) (float) $weightInput : 0;
        $criteriaSum = 0;
        $position = 0;

        foreach ($this->criteria as $criterion) {
            if ($criterion['op'] === 'delete') {
                continue;
            }

            $position++;
            $nombre = trim((string) $criterion['nombre']);
            $ponderacion = $criterion['ponderacion'];

            if ($nombre === '') {
                $errors[] = "La evaluación #{$position} debe tener un nombre.";
            }

            if (! is_numeric($ponderacion)
                || (float) $ponderacion !== (float) (int) (float) $ponderacion
                || (int) (float) $ponderacion < 1
                || (int) (float) $ponderacion > BusinessRules::EVALUATION_TOTAL_PERCENT) {
                $label = $nombre !== '' ? "«{$nombre}»" : "#{$position}";
                $errors[] = "La ponderación de {$label} debe ser un valor numérico entero entre 1 y 100.";

                continue;
            }

            $criteriaSum += (int) (float) $ponderacion;
        }

        if ($weightOk) {
            try {
                app(EvaluationService::class)->assertWeightsSumTo100($weight, $criteriaSum);
            } catch (ValidationException $exception) {
                $errors[] = collect($exception->errors())->flatten()->first() ?? 'Error de validación.';
            }
        }

        $names = array_column($this->rows, 'full_name', 'id');

        foreach ($this->pendingNotas as $notaKey => $value) {
            if (is_numeric($value)
                && (float) $value >= BusinessRules::GRADE_MIN
                && (float) $value <= BusinessRules::GRADE_MAX) {
                continue;
            }

            $preinscriptionId = (int) explode(':', (string) $notaKey, 2)[0];
            $fullName = $names[$preinscriptionId] ?? 'Participante #'.$preinscriptionId;
            $errors[] = "{$fullName}: la nota {$value} está fuera del rango 0 a 100.";
        }

        if ($errors !== []) {
            $this->lastError = implode("\n", $errors);
            Notification::make()
                ->title('No se pudieron guardar los cambios')
                ->body(implode(' · ', $errors))
                ->danger()
                ->send();

            return;
        }

        $gradeWrites = [];

        if ($this->pendingNotas !== []) {
            $scopedPreinscriptionIds = Preinscription::query()
                ->whereHas('group', fn ($query) => $query->where('course_id', $course->id))
                ->whereHas('group.course', fn ($query) => $query->where('instructor_id', auth()->id()))
                ->pluck('id')
                ->flip();

            foreach ($this->pendingNotas as $notaKey => $value) {
                $parts = explode(':', (string) $notaKey, 2);

                if (count($parts) !== 2 || ! isset($scopedPreinscriptionIds[(int) $parts[0]])) {
                    continue;
                }

                $gradeWrites[(int) $parts[0]][(int) $parts[1]] = $value;
            }
        }

        try {
            app(EvaluationService::class)->applyEvaluationConfig(
                $course,
                $weight,
                $this->criteria,
                $gradeWrites,
            );
        } catch (ValidationException $exception) {
            $this->lastError = collect($exception->errors())->flatten()->first() ?? 'Error de validación.';
            Notification::make()
                ->title($this->lastError)
                ->danger()
                ->send();

            return;
        }

        $this->pendingNotas = [];
        $this->lastError = null;
        $this->syncAttendanceWeightInput();
        $this->loadMatrix();

        Group::where('course_id', $course->id)
            ->pluck('id')
            ->each(fn (int $groupId) => GroupSheetsNeedRefresh::dispatch($groupId));

        Notification::make()
            ->title('Cambios guardados correctamente')
            ->success()
            ->send();
    }

    /** Discard every staged change and reload the database state. */
    public function discardChanges(): void
    {
        $this->pendingNotas = [];
        $this->lastError = null;
        $this->syncAttendanceWeightInput();
        $this->loadMatrix();
    }

    /** @return array<int, string> */
    private function buildCourseOptions(): array
    {
        return Course::query()
            ->where('instructor_id', auth()->id())
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Course $course) => [$course->id => $course->nombre])
            ->toArray();
    }

    /** @return array<int, string> */
    private function buildGroupOptions(): array
    {
        if ($this->selectedCourseId === null) {
            return [];
        }

        return Group::query()
            ->where('course_id', $this->selectedCourseId)
            ->whereHas('course', fn ($query) => $query->where('instructor_id', auth()->id()))
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->mapWithKeys(fn (Group $group) => [$group->id => $group->nombre])
            ->toArray();
    }

    private function commitCourseSelection(): void
    {
        $this->appliedCourseId = $this->selectedCourseId;
        $this->selectedGroupId = null;
        $this->appliedGroupId = null;
        $this->groupOptions = $this->buildGroupOptions();
        $this->syncAttendanceWeightInput();
        $this->resetMatrix();

        if (count($this->groupOptions) === 1) {
            $this->selectedGroupId = (int) array_key_first($this->groupOptions);
            $this->appliedGroupId = $this->selectedGroupId;
            $this->loadMatrix();
        }
    }

    private function syncAttendanceWeightInput(): void
    {
        $course = $this->scopedCourse();

        $this->dbAttendanceWeight = $course
            ? (int) ($course->attendance_weight ?? BusinessRules::DEFAULT_ATTENDANCE_WEIGHT)
            : BusinessRules::DEFAULT_ATTENDANCE_WEIGHT;
        $this->attendanceWeightInput = $this->dbAttendanceWeight;
    }

    private function scopedCourse(): ?Course
    {
        if ($this->selectedCourseId === null) {
            return null;
        }

        return Course::query()
            ->where('instructor_id', auth()->id())
            ->find($this->selectedCourseId);
    }

    private function scopedGroup(): ?Group
    {
        if ($this->selectedGroupId === null) {
            return null;
        }

        return Group::query()
            ->whereHas('course', fn ($query) => $query->where('instructor_id', auth()->id()))
            ->with(['course', 'sessions'])
            ->find($this->selectedGroupId);
    }

    /** Next unique staging id for an op=add entry (negative, never collides with real ids). */
    private function nextStagingId(): int
    {
        $ids = array_filter(
            array_column($this->criteria, 'id'),
            static fn ($id): bool => $id !== null,
        );
        $lowest = $ids === [] ? 0 : (int) min($ids);

        return min(0, $lowest) - 1;
    }

    private function dropPendingNotasFor(int $criteriaKey): void
    {
        foreach (array_keys($this->pendingNotas) as $notaKey) {
            if (str_ends_with((string) $notaKey, ':'.$criteriaKey)) {
                unset($this->pendingNotas[$notaKey]);
            }
        }
    }

    private function resetMatrix(): void
    {
        $this->criteria = [];
        $this->rows = [];
        $this->sessionCount = 0;
        $this->lastError = null;
    }

    private static function trimNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
