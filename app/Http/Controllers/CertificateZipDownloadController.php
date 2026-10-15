<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Group;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificateZipDownloadController extends Controller
{
    public function downloadCourse(Request $request, Course $course, CertificateService $service): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $isAdmin = $user->hasRole(UserRole::ADMIN->value);
        $isInstructor = $user->hasRole(UserRole::INSTRUCTOR->value)
            && $course->instructor_id !== null
            && (int) $course->instructor_id === (int) $user->id;

        abort_unless($isAdmin || $isInstructor, 403);

        return $service->downloadCourseCertificatesZip($course);
    }

    public function downloadGroup(Request $request, Group $group, CertificateService $service): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $group->loadMissing('course');
        $course = $group->course;

        $isAdmin = $user->hasRole(UserRole::ADMIN->value);
        $isInstructor = $user->hasRole(UserRole::INSTRUCTOR->value)
            && $course !== null
            && $course->instructor_id !== null
            && (int) $course->instructor_id === (int) $user->id;

        abort_unless($isAdmin || $isInstructor, 403);

        return $service->downloadGroupCertificatesZip($group);
    }
}
