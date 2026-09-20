<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Group;
use App\Models\Preinscription;
use App\Services\PreinscriptionService;
use App\Support\BusinessRules;
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

    public bool $stepConfirmation = false;

    public ?Group $group = null;

    public $availableGroups = [];

    public function mount(Group|int $group): void
    {
        $groupModel = $group instanceof Group ? $group : Group::with('course')->findOrFail($group);
        $this->groupId = $groupModel->id;
        $this->group = $groupModel;
        $this->availableGroups = app(PreinscriptionService::class)->getAvailableGroups($this->group->course_id);
    }

    public function updatedGroupId(): void
    {
        if ($this->groupId) {
            $this->group = Group::with('course')->find($this->groupId);
        }
    }

    public function getPrecioCalculadoProperty(): ?float
    {
        if (! $this->tipoParticipante || ! $this->group) {
            return null;
        }

        return BusinessRules::calculatePrice(
            (int) $this->group->course->carga_horaria,
            $this->tipoParticipante
        );
    }

    public function goToConfirmation(): void
    {
        $this->validate([
            'groupId' => 'required|integer|exists:groups,id',
            'ci' => 'required|string|max:20',
            'nombres' => 'required|string|max:100',
            'apellidoPaterno' => 'required|string|max:100',
            'apellidoMaterno' => 'nullable|string|max:100',
            'celular' => 'nullable|string|max:20',
            'email' => 'required|email|max:150',
            'tipoParticipante' => 'required|in:umss,externo,auxiliar',
        ]);

        $exists = Preinscription::where('ci', $this->ci)
            ->where('group_id', $this->groupId)
            ->exists();

        if ($exists) {
            $this->addError('ci', 'Ya cuentas con una preinscripcion activa para este curso.');

            return;
        }

        $this->stepConfirmation = true;
    }

    public function backToEdit(): void
    {
        $this->stepConfirmation = false;
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

        $service->register([
            'group_id' => $validated['groupId'],
            'ci' => $validated['ci'],
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellidoPaterno'],
            'apellido_materno' => $validated['apellidoMaterno'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'email' => $validated['email'],
            'tipo_participante' => $validated['tipoParticipante'],
        ]);

        session()->flash('success', 'Preinscripcion registrada correctamente. Siga las instrucciones de pago en caja facultativa.');

        $this->reset(['ci', 'nombres', 'apellidoPaterno', 'apellidoMaterno', 'celular', 'email', 'tipoParticipante', 'stepConfirmation']);
    }

    public function render()
    {
        return view('livewire.preinscription-component', [
            'group' => $this->group,
            'availableGroups' => $this->availableGroups,
        ]);
    }
}
