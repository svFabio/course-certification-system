<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

class PreinscriptionComponent extends Component
{
    public ?int $groupId = null;

    public ?string $ci = null;

    public ?string $nombres = null;

    public ?string $apellidoPaterno = null;

    public ?string $apellidoMaterno = null;

    public ?string $celular = null;

    public ?string $email = null;

    public ?string $tipoParticipante = null;

    public function submit(): void
    {
        // TODO: Validate group capacity
        // TODO: Create preinscription record
        // TODO: Redirect to payment
    }

    public function render()
    {
        return view('livewire.preinscription-component');
    }
}
