<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Course;
use Livewire\Component;

class CatalogComponent extends Component
{
    public ?string $search = null;

    public ?string $periodo = null;

    public function render()
    {
        $courses = Course::query()
            ->where('status', 'publicado')
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', "%{$this->search}%"))
            ->when($this->periodo, fn ($q) => $q->where('periodo', $this->periodo))
            ->with(['instructor', 'groups' => fn ($g) => $g->where('status', 'habilitado')])
            ->get();

        return view('livewire.catalog-component', ['courses' => $courses]);
    }
}
