<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Certificate;
use App\Services\CertificateService;
use App\Services\EvaluationService;
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

        $finalGrade = (! $record->isInstructorCertificate() && $record->preinscription)
            ? app(EvaluationService::class)->finalGrade($record->preinscription)
            : null;

        $this->certificate = [
            'nombre' => $record->beneficiaryName(),
            'rol' => $record->isInstructorCertificate() ? 'Instructor' : 'Participante',
            'curso' => $record->course?->nombre,
            'tipo' => $record->tipo?->getLabel(),
            'nota_final' => $finalGrade !== null ? number_format($finalGrade, 1).' / 100' : null,
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
        ];
    }

    public function render(): View
    {
        return view('livewire.certificate-verification-component');
    }
}
