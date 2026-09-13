<?php
declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

class CertificateVerificationComponent extends Component
{
    public ?string $codigo = null;
    public ?array $certificate = null;
    public bool $searched = false;

    public function verify(): void
    {
        // TODO: Look up certificate by codigo_unico
        // TODO: Return certificate details if found
        $this->searched = true;
    }

    public function render()
    {
        return view('livewire.certificate-verification-component');
    }
}
