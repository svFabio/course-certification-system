<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\Session;
use App\Services\AttendanceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use InvalidArgumentException;
use Livewire\Attributes\Url;

class MarkAttendance extends Page
{
    protected static string $view = 'filament.instructor.pages.mark-attendance';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Mis Cursos';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Marcar Asistencia';

    protected static ?string $navigationLabel = 'Marcar Asistencia';

    #[Url(as: 'group')]
    public ?int $selectedGroupId = null;

    /** @var array<int|string, string> */
    public array $groupOptions = [];

    /** @var array<int, array{id: int, fecha: string, label: string}> */
    public array $sessions = [];

    /** @var array<int, array{id: int, ci: string, full_name: string}> */
    public array $students = [];

    /**
     * Key: "{preinscriptionId}_{sessionId}"
     * Value: AttendanceStatus value string or null.
     *
     * @var array<string, string|null>
     */
    public array $attendanceData = [];

    public bool $saved = false;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole(UserRole::INSTRUCTOR->value);
    }

    public function getHeading(): string
    {
        return 'Control de Asistencia por Grupo';
    }

    public function mount(): void
    {
        $this->groupOptions = Group::whereHas('course', fn ($q) => $q->where('instructor_id', auth()->id()))
            ->with('course')
            ->get()
            ->mapWithKeys(fn (Group $g) => [
                $g->id => $g->course->nombre.' — '.$g->nombre,
            ])
            ->toArray();

        if ($this->selectedGroupId !== null && ! isset($this->groupOptions[$this->selectedGroupId])) {
            $this->selectedGroupId = null;
        }

        if ($this->selectedGroupId === null && ! empty($this->groupOptions)) {
            $this->selectedGroupId = (int) array_key_first($this->groupOptions);
        }

        if ($this->selectedGroupId) {
            $this->loadGroupData();
        }
    }

    /** @return array<string, string> */
    public function getStatusOptionsProperty(): array
    {
        $options = [];
        foreach (AttendanceStatus::cases() as $status) {
            $options[$status->value] = $status->getLabel() ?? ucfirst($status->value);
        }

        return $options;
    }

    /**
     * Enum case values exposed to Blade so the view never references magic strings.
     *
     * @return array<string, string>
     */
    public function getEnumValuesProperty(): array
    {
        return [
            'presente' => AttendanceStatus::PRESENTE->value,
            'ausente' => AttendanceStatus::AUSENTE->value,
            'justificado' => AttendanceStatus::JUSTIFICADO->value,
        ];
    }

    public function updatedSelectedGroupId(): void
    {
        $this->loadGroupData();
    }

    public function loadGroupData(): void
    {
        $this->resetData();
        $this->saved = false;

        if (! $this->selectedGroupId) {
            return;
        }

        $group = $this->scopedGroup();

        if (! $group) {
            $this->selectedGroupId = null;

            Notification::make()
                ->title('El grupo seleccionado no existe o no pertenece a sus cursos.')
                ->danger()
                ->send();

            return;
        }

        $this->sessions = Session::where('group_id', $group->id)
            ->orderBy('fecha')
            ->get()
            ->map(fn (Session $s) => [
                'id' => $s->id,
                'fecha' => $s->fecha->format('d/m'),
                'label' => $s->fecha->format('d/m/Y'),
            ])
            ->toArray();

        $this->students = Preinscription::where('group_id', $group->id)
            ->where('status', PreinscriptionStatus::INSCRITO)
            ->get()
            ->map(fn (Preinscription $p) => [
                'id' => $p->id,
                'ci' => $p->ci,
                'full_name' => $p->full_name,
            ])
            ->toArray();

        $this->loadExistingAttendance();
    }

    public function markAttendance(string $key, ?string $status): void
    {
        $this->attendanceData[$key] = ($status === '' || $status === null) ? null : $status;
        $this->saved = false;
    }

    public function markAllForSession(int $sessionId, string $status): void
    {
        foreach ($this->students as $student) {
            $key = "{$student['id']}_{$sessionId}";
            $this->attendanceData[$key] = $status;
        }
        $this->saved = false;
    }

    public function save(): void
    {
        if (empty($this->selectedGroupId) || empty($this->attendanceData)) {
            return;
        }

        $group = $this->scopedGroup();

        if (! $group) {
            $this->rejectSave('El grupo seleccionado no existe o no pertenece a sus cursos.');

            return;
        }

        try {
            app(AttendanceService::class)->registerManualBatch($group, $this->attendanceData);
        } catch (InvalidArgumentException $exception) {
            $this->rejectSave($exception->getMessage());

            return;
        }

        $this->saved = true;

        Notification::make()
            ->title('Asistencia guardada correctamente')
            ->success()
            ->send();
    }

    /**
     * Re-validates the group against an instructor-scoped query, mirroring
     * GradeMatrix::scopedGroup() so client-supplied ids can never widen access.
     */
    private function scopedGroup(): ?Group
    {
        if ($this->selectedGroupId === null) {
            return null;
        }

        return Group::query()
            ->whereHas('course', fn ($query) => $query->where('instructor_id', auth()->id()))
            ->find($this->selectedGroupId);
    }

    private function rejectSave(string $message): void
    {
        $this->saved = false;

        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    private function loadExistingAttendance(): void
    {
        $sessionIds = array_column($this->sessions, 'id');
        $studentIds = array_column($this->students, 'id');

        if (empty($sessionIds) || empty($studentIds)) {
            return;
        }

        $existing = Attendance::whereIn('session_id', $sessionIds)
            ->whereIn('preinscription_id', $studentIds)
            ->get()
            ->keyBy(fn (Attendance $a) => "{$a->preinscription_id}_{$a->session_id}");

        foreach ($this->students as $student) {
            foreach ($this->sessions as $session) {
                $key = "{$student['id']}_{$session['id']}";
                $status = $existing->get($key)?->status;
                $this->attendanceData[$key] = $status instanceof AttendanceStatus ? $status->value : ($status ? (string) $status : null);
            }
        }
    }

    private function resetData(): void
    {
        $this->sessions = [];
        $this->students = [];
        $this->attendanceData = [];
    }
}
