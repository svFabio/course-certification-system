<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Symfony\Component\HttpFoundation\Response;

class PublicCertificateDownloadController extends Controller
{
    public function __invoke(string $codigo, CertificateService $service): Response
    {
        $certificate = Certificate::query()
            ->where('codigo_unico', trim($codigo))
            ->first();

        abort_unless($certificate !== null, 404, 'Certificado no encontrado.');

        return $service->download($certificate);
    }
}
