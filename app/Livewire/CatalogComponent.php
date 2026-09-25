<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use App\Models\Course;
use App\Services\CourseService;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogComponent extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?string $periodo = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriodo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        app(CourseService::class)->closeExpiredPreinscriptions();

        $periods = Course::where('status', CourseStatus::PUBLICADO)
            ->distinct()
            ->orderByDesc('periodo')
            ->pluck('periodo');

        $courses = Course::query()
            ->where('status', CourseStatus::PUBLICADO)
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->periodo, fn ($q) => $q->where('periodo', $this->periodo))
            ->with([
                'instructor',
                'groups' => fn ($g) => $g->where('status', GroupStatus::HABILITADO),
            ])
            ->paginate(12);

        return view('livewire.catalog-component', [
            'courses' => $courses,
            'periods' => $periods,
        ]);
    }
}
