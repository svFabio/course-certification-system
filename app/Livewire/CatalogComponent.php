<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Models\Course;
use App\Models\Group;
use App\Services\CourseInterestService;
use App\Support\BusinessRules;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogComponent extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?string $periodo = null;

    public ?string $nivel = null;

    public ?string $cargaHoraria = null;

    public ?int $interestCourseId = null;

    public ?string $interestEmail = null;

    public bool $interestSubmitted = false;

    public bool $interestAlreadyRegistered = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodo(): void
    {
        $this->resetPage();
    }

    public function updatingNivel(): void
    {
        $this->resetPage();
    }

    public function updatingCargaHoraria(): void
    {
        $this->resetPage();
    }

    public function getNivelesProperty(): array
    {
        return CourseLevel::values();
    }

    public function getCargasProperty(): array
    {
        return BusinessRules::VALID_HOURS;
    }

    public function openInterest(int $courseId): void
    {
        $this->interestCourseId = $courseId;
        $this->interestEmail = null;
        $this->interestSubmitted = false;
        $this->interestAlreadyRegistered = false;
        $this->resetValidation('interestEmail');
    }

    public function closeInterest(): void
    {
        $this->interestCourseId = null;
        $this->interestEmail = null;
        $this->interestSubmitted = false;
        $this->interestAlreadyRegistered = false;
        $this->resetValidation('interestEmail');
    }

    public function registerInterest(CourseInterestService $service): void
    {
        $validated = $this->validate([
            'interestCourseId' => ['required', 'integer', 'exists:courses,id'],
            'interestEmail' => ['required', 'email:rfc', 'max:150'],
        ], [
            'interestCourseId.exists' => 'El curso seleccionado ya no está disponible.',
            'interestEmail.required' => 'Ingrese su correo electrónico.',
            'interestEmail.email' => 'Ingrese una dirección de correo electrónico válida.',
        ]);

        $course = Course::findOrFail($validated['interestCourseId']);

        if ($course->status !== CourseStatus::PUBLICADO) {
            $this->addError('interestCourseId', 'El curso ya no acepta registros de interés.');

            return;
        }

        $interest = $service->register($course, $validated['interestEmail'], auth()->user());

        $this->interestSubmitted = true;
        $this->interestAlreadyRegistered = ! $interest->wasRecentlyCreated;
        $this->interestEmail = null;
    }

    public function render()
    {
        $periods = Course::where('status', CourseStatus::PUBLICADO)
            ->distinct()
            ->orderByDesc('periodo')
            ->pluck('periodo');

        $courses = Course::query()
            ->where('status', CourseStatus::PUBLICADO)
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->periodo, fn ($q) => $q->where('periodo', $this->periodo))
            ->when($this->nivel, fn ($q) => $q->where('nivel', $this->nivel))
            ->when($this->cargaHoraria, fn ($q) => $q->where('carga_horaria', $this->cargaHoraria))
            ->with([
                'instructor',
                'groups' => function ($g) {
                    $g->whereIn('status', [GroupStatus::HABILITADO, GroupStatus::NO_HABILITADO])
                        ->withCount(['preinscriptions as inscritos_count' => fn ($q) => $q->whereIn('status', [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO]),
                        ]);
                },
            ])
            ->paginate(12);

        $atCapacity = $courses->getCollection()->mapWithKeys(function (Course $course): array {
            $hasFreeSeat = $course->groups->contains(
                fn (Group $group): bool => (int) ($group->inscritos_count ?? 0) < $group->cupo_maximo
            );

            return [$course->id => ! $hasFreeSeat];
        });

        return view('livewire.catalog-component', [
            'courses' => $courses,
            'periods' => $periods,
            'atCapacity' => $atCapacity,
        ]);
    }
}
