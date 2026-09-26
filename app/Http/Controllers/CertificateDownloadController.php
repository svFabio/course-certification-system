<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CertificateDownloadController extends Controller
{
    public function __invoke(Request $request, Certificate $certificate, CertificateService $service): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $certificate->loadMissing(['preinscription', 'instructor']);

        $ownsAsStudent = $user->hasRole(UserRole::STUDENT->value)
            && $certificate->preinscription?->email === $user->email;

        $ownsAsInstructor = $user->hasRole(UserRole::INSTRUCTOR->value)
            && $certificate->instructor_id !== null
            && (int) $certificate->instructor_id === (int) $user->id;

        $isAdmin = $user->hasRole(UserRole::ADMIN->value);

        abort_unless($ownsAsStudent || $ownsAsInstructor || $isAdmin, 403);

        return $service->download($certificate);
    }
}
