<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicCertificateScannedDownloadController extends Controller
{
    public function __invoke(Request $request, string $codigo, CertificateService $service): Response
    {
        $searchCode = strtoupper(trim($codigo));

        $certificate = Certificate::query()
            ->where(function ($query) use ($searchCode) {
                $query->where('codigo_unico', $searchCode)
                    ->orWhereRaw('UPPER(codigo_unico) = ?', [$searchCode]);
            })
            ->first();

        abort_unless($certificate !== null, 404, 'Certificado no encontrado.');
        abort_unless($certificate->hasScannedCopy(), 404, 'Este certificado aún no cuenta con copia digitalizada o firmada.');

        $inline = ! $request->boolean('download');

        return $service->downloadScanned($certificate, $inline);
    }
}
