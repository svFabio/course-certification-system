<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Certificate;
use Livewire\Component;

class CertificateVerificationComponent extends Component
{
    public ?string $codigo = null;

    public ?array $certificate = null;

    public bool $searched = false;

    public function verify(): void
    {
        $this->validate([
            'codigo' => 'required|string|max:50',
        ]);

        $this->searched = true;
        $this->certificate = null;

        $record = Certificate::with(['preinscription', 'course'])
            ->where('codigo_unico', trim($this->codigo))
            ->first();

        if ($record) {
            $this->certificate = [
                'nombre' => $record->preinscription?->full_name,
                'curso' => $record->course?->nombre,
                'tipo' => $record->tipo?->getLabel(),
                'emitido_en' => $record->emitido_en?->format('d/m/Y'),
                'signature_status' => $record->signature_status?->getLabel(),
                'codigo_unico' => $record->codigo_unico,
            ];
        }
    }

    public function render()
    {
        return view('livewire.certificate-verification-component');
    }
}
