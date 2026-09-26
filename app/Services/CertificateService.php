<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Support\Str;

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

    public function regeneratePdf(Certificate $certificate): Certificate
    {
        // PDF generation will be implemented when a PDF library is integrated.
        // For now, mark the path so the caller knows it needs generation.
        $certificate->update(['pdf_path' => null]);

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
