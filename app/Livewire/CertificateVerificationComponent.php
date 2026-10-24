<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Contracts\View\View;
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
        $this->codigo = trim((string) $this->codigo);

        $this->validate([
            'codigo' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[a-zA-Z0-9\-_]+$/'],
        ], [
            'codigo.regex' => 'El código solo debe contener caracteres alfanuméricos y guiones (ej. CERT-XXXXXXXX).',
        ]);

        $this->searched = true;
        $this->certificate = null;

        $searchCode = strtoupper($this->codigo);

        $record = Certificate::with(['preinscription', 'instructor', 'course.groups.sessions'])
            ->where(function ($query) use ($searchCode) {
                $query->where('codigo_unico', $searchCode)
                    ->orWhereRaw('UPPER(codigo_unico) = ?', [$searchCode]);
            })
            ->first();

        if ($record === null) {
            return;
        }

        $dates = app(CertificateService::class)->courseDateRange($record->course);

        $hasScanned = $record->hasScannedCopy();
        $isScannedImage = false;
        if ($hasScanned && filled($record->pdf_escaneado_path)) {
            $ext = strtolower(pathinfo((string) $record->pdf_escaneado_path, PATHINFO_EXTENSION));
            $isScannedImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
        }

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
            'valido' => true,
            'estado_validez' => 'Certificado válido',
            'codigo_unico' => $record->codigo_unico,
            'download_url' => route('certificado.publico.descargar', ['codigo' => $record->codigo_unico]),
            'has_scanned' => $hasScanned,
            'scanned_url' => $hasScanned ? route('certificado.publico.digital', ['codigo' => $record->codigo_unico]) : null,
            'scanned_type' => $isScannedImage ? 'image' : 'pdf',
            'escaneado_en' => $record->escaneado_en?->format('d/m/Y'),
        ];
    }

    public function render(): View
    {
        return view('livewire.certificate-verification-component');
    }
}
