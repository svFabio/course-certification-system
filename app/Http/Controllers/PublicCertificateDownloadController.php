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
        $searchCode = strtoupper(trim($codigo));

        $certificate = Certificate::query()
            ->where(function ($query) use ($searchCode) {
                $query->where('codigo_unico', $searchCode)
                    ->orWhereRaw('UPPER(codigo_unico) = ?', [$searchCode]);
            })
            ->first();

        abort_unless($certificate !== null, 404, 'Certificado no encontrado.');

        return $service->download($certificate);
    }
}
