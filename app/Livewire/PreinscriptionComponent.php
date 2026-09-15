<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Group;
use App\Services\PreinscriptionService;
use Livewire\Attributes\Url;
use Livewire\Component;

class PreinscriptionComponent extends Component
{
    #[Url]
    public ?int $groupId = null;

    public ?string $ci = null;

    public ?string $nombres = null;

    public ?string $apellidoPaterno = null;

    public ?string $apellidoMaterno = null;

    public ?string $celular = null;

    public ?string $email = null;

    public ?string $tipoParticipante = null;

    public ?Group $group = null;

    public function mount(int $group): void
    {
        $this->groupId = $group;
        $this->group = Group::with('course')->findOrFail($group);
    }

    public function submit(PreinscriptionService $service): void
    {
        $validated = $this->validate([
            'groupId' => 'required|integer|exists:groups,id',
            'ci' => 'required|string|max:20',
            'nombres' => 'required|string|max:100',
            'apellidoPaterno' => 'required|string|max:100',
            'apellidoMaterno' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:20',
            'email' => 'required|email|max:150',
            'tipoParticipante' => 'required|in:umss,externo,auxiliar',
        ]);

        $preinscription = $service->register([
            'group_id' => $validated['groupId'],
            'ci' => $validated['ci'],
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellidoPaterno'],
            'apellido_materno' => $validated['apellidoMaterno'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'email' => $validated['email'],
            'tipo_participante' => $validated['tipoParticipante'],
        ]);

        session()->flash('success', 'Your pre-registration has been submitted successfully. Check your email for payment instructions.');

        $this->reset(['ci', 'nombres', 'apellidoPaterno', 'apellidoMaterno', 'celular', 'email', 'tipoParticipante']);
    }

    public function render()
    {
        return view('livewire.preinscription-component', [
            'group' => $this->group,
        ]);
    }
}
