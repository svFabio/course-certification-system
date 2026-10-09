<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Services\CertificateService;
use Livewire\Attributes\Url;
use Livewire\Component;

class CertificateVerificationComponent extends Component
{
    #[Url]
    public ?string $codigo = null;

    public ?array $certificate = null;

    public bool $searched = false;

    public function mount(): void
    {
        if (filled($this->codigo)) {
            $this->verify();
        }
    }

    public function verify(): void
    {
        $this->validate([
            'codigo' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[a-zA-Z0-9\-_]+$/'],
        ], [
            'codigo.regex' => 'El código solo debe contener caracteres alfanuméricos y guiones (ej. CERT-XXXXXXXX).',
        ]);

        $this->searched = true;
        $this->certificate = null;

        $record = Certificate::with(['preinscription', 'instructor', 'course.groups.sessions'])
            ->where('codigo_unico', trim($this->codigo))
            ->first();

        if ($record === null) {
            return;
        }

        $dates = app(CertificateService::class)->courseDateRange($record->course);
        $isReady = $record->signature_status === SignatureStatus::LISTO;

        $this->certificate = [
            'nombre' => $record->beneficiaryName(),
            'rol' => $record->isInstructorCertificate() ? 'Instructor' : 'Participante',
            'curso' => $record->course?->nombre,
            'tipo' => $record->tipo?->getLabel(),
            'grupos' => $record->isInstructorCertificate() ? ($record->grupos_detalle ?: null) : null,
            'carga_horaria' => $record->course?->carga_horaria
                ? $record->course->carga_horaria.' horas'
                : null,
            'fechas_curso' => $dates['rango'],
            'emitido_en' => $record->emitido_en?->format('d/m/Y'),
            'signature_status' => $record->signature_status?->getLabel(),
            'valido' => $isReady,
            'estado_validez' => $isReady ? 'Certificado válido' : 'Registrado — pendiente de firmas',
            'codigo_unico' => $record->codigo_unico,
        ];
    }

    public function render()
    {
        return view('livewire.certificate-verification-component');
    }
}
