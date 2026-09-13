<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Certificate;
use App\Models\Preinscription;

class CertificateService
{
    public function __construct() {}

    // TODO: Implement generate — create certificate with unique code and QR
    public function generate(Preinscription $preinscription): Certificate
    {
        throw new \RuntimeException('TODO: Implement generate');
    }

    // TODO: Implement calculateCertificateType — delegate to BusinessRules
    public function calculateCertificateType(float $finalGrade): string
    {
        throw new \RuntimeException('TODO: Implement calculateCertificateType');
    }

    // TODO: Implement getSignatureStatus — return current signature chain status
    public function getSignatureStatus(Certificate $certificate): string
    {
        throw new \RuntimeException('TODO: Implement getSignatureStatus');
    }

    // TODO: Implement regeneratePdf — regenerate PDF with updated signatures
    public function regeneratePdf(Certificate $certificate): Certificate
    {
        throw new \RuntimeException('TODO: Implement regeneratePdf');
    }
}
