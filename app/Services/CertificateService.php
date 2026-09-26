<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    public function __construct(
        private readonly EvaluationService $evaluationService,
    ) {}

    public function generate(Preinscription $preinscription): Certificate
    {
        $finalGrade = $this->evaluationService->finalGrade($preinscription);
        $type = $this->calculateCertificateType($finalGrade);

        return Certificate::create([
            'preinscription_id' => $preinscription->id,
            'course_id' => $preinscription->group->course_id,
            'tipo' => $type,
            'codigo_unico' => $this->generateUniqueCode(),
            'signature_status' => SignatureStatus::PENDIENTE,
            'emitido_en' => now(),
        ]);
    }

    public function calculateCertificateType(float $finalGrade): CertificateType
    {
        return BusinessRules::determineCertificateType($finalGrade);
    }

    public function getSignatureStatus(Certificate $certificate): SignatureStatus
    {
        return $certificate->signature_status;
    }

    public function isReady(Certificate $certificate): bool
    {
        return $certificate->signature_status === SignatureStatus::LISTO;
    }

    public function verificationUrl(Certificate $certificate): string
    {
        return route('certificado.verificar', ['codigo' => $certificate->codigo_unico], absolute: true);
    }

    public function generatePdf(Certificate $certificate)
    {
        if (! $this->isReady($certificate)) {
            throw ValidationException::withMessages([
                'certificate' => 'El certificado aún no está listo para descargar.',
            ]);
        }

        $certificate->loadMissing(['preinscription', 'course.instructor']);

        $verifyUrl = $this->verificationUrl($certificate);

        $qrDataUri = null;
        try {
            $qrDataUri = 'data:image/png;base64,'.base64_encode(
                QrCode::format('png')->size(180)->margin(1)->generate($verifyUrl)
            );
        } catch (\Throwable) {
            $qrDataUri = null;
        }

        return Pdf::loadView('pdf.certificado', [
            'certificado' => [
                'participante' => $certificate->preinscription?->full_name ?? 'Participante',
                'curso' => $certificate->course?->nombre ?? 'Curso',
                'tipo' => $certificate->tipo?->getLabel() ?? 'Certificado',
                'emitido_en' => $certificate->emitido_en?->format('d / m / Y') ?? now()->format('d / m / Y'),
                'codigo_unico' => $certificate->codigo_unico,
                'instructor' => $certificate->course?->instructor?->name ?? 'Docente',
                'url_verificacion' => $verifyUrl,
                'qr_data_uri' => $qrDataUri,
            ],
        ])->setPaper('a4', 'landscape');
    }

    public function download(Certificate $certificate)
    {
        $pdf = $this->generatePdf($certificate);
        $filename = 'certificado-'.Str::slug($certificate->codigo_unico).'.pdf';

        return $pdf->download($filename);
    }

    public function regeneratePdf(Certificate $certificate): Certificate
    {
        if (! $this->isReady($certificate)) {
            throw ValidationException::withMessages([
                'certificate' => 'El certificado aún no está listo para generar PDF.',
            ]);
        }

        $pdf = $this->generatePdf($certificate);
        $path = 'certificates/'.$certificate->codigo_unico.'.pdf';

        try {
            Storage::disk('cloudinary')->put($path, $pdf->output(), [
                'resource_type' => 'raw',
            ]);
        } catch (\Throwable) {
            Storage::disk('local')->put($path, $pdf->output());
        }

        $certificate->update(['pdf_path' => $path]);

        return $certificate->fresh();
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'CERT-'.now()->year.'-'.strtoupper(Str::random(8));
        } while (Certificate::where('codigo_unico', $code)->exists());

        return $code;
    }
}
