<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CertificateType;
use App\Enums\PreinscriptionStatus;
use App\Enums\SignatureStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Preinscription;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use App\Support\BusinessRules;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CertificateService
{
    public function __construct(
        private readonly EvaluationService $evaluationService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function generate(Preinscription $preinscription): Certificate
    {
        $preinscription->loadMissing(['group.course', 'certificate']);

        if ($preinscription->status !== PreinscriptionStatus::INSCRITO) {
            throw ValidationException::withMessages([
                'preinscription' => 'Solo se emiten certificados a estudiantes con inscripción confirmada.',
            ]);
        }

        if ($preinscription->certificate !== null) {
            throw ValidationException::withMessages([
                'preinscription' => 'Esta preinscripción ya tiene un certificado emitido.',
            ]);
        }

        if ($preinscription->group === null || $preinscription->group->course_id === null) {
            throw ValidationException::withMessages([
                'preinscription' => 'La preinscripción no está asociada a un curso válido.',
            ]);
        }

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

    /**
     * @return Collection<int, Certificate>
     */
    public function generateForCourse(Course $course): Collection
    {
        $course->loadMissing([
            'groups.preinscriptions.grades.evaluationCriteria',
            'groups.preinscriptions.attendances',
            'groups.sessions',
            'instructor',
        ]);

        $created = collect();

        DB::transaction(function () use ($course, &$created): void {
            foreach ($course->groups as $group) {
                foreach ($group->preinscriptions as $preinscription) {
                    if ($preinscription->status !== PreinscriptionStatus::INSCRITO) {
                        continue;
                    }

                    if ($preinscription->certificate()->exists()) {
                        continue;
                    }

                    $preinscription->setRelation('group', $group);
                    $created->push($this->generate($preinscription));
                }
            }

            $created = $created->merge($this->generateInstructorCertificates($course, onePerGroup: false));
        });

        return $created;
    }

    /**
     * HU-32: emit instructor participation certificate(s).
     *
     * @return Collection<int, Certificate>
     */
    public function generateInstructorCertificates(Course $course, bool $onePerGroup = false): Collection
    {
        $course->loadMissing(['groups', 'instructor']);

        if ($course->instructor === null) {
            return collect();
        }

        $created = collect();

        if ($onePerGroup) {
            foreach ($course->groups as $group) {
                $exists = Certificate::query()
                    ->where('course_id', $course->id)
                    ->where('instructor_id', $course->instructor_id)
                    ->where('tipo', CertificateType::INSTRUCTOR)
                    ->where('grupos_detalle', $group->nombre)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $created->push($this->createInstructorCertificate($course, $group->nombre));
            }

            return $created;
        }

        $groupNames = $course->groups->pluck('nombre')->filter()->values()->implode(', ');
        if ($groupNames === '') {
            $groupNames = 'Sin grupos';
        }

        $any = Certificate::query()
            ->where('course_id', $course->id)
            ->where('instructor_id', $course->instructor_id)
            ->where('tipo', CertificateType::INSTRUCTOR)
            ->whereNull('preinscription_id')
            ->exists();

        if (! $any) {
            $created->push($this->createInstructorCertificate($course, $groupNames));
        }

        return $created;
    }

    public function calculateCertificateType(float $finalGrade): CertificateType
    {
        return BusinessRules::determineCertificateType($finalGrade);
    }

    public function isReady(Certificate $certificate): bool
    {
        return $certificate->signature_status === SignatureStatus::LISTO;
    }

    public function verificationUrl(Certificate $certificate): string
    {
        return route('certificado.verificar', ['codigo' => $certificate->codigo_unico], absolute: true);
    }

    /**
     * HU-29: advance one step in the authority signature workflow.
     *
     * @throws ValidationException
     */
    public function advanceSignature(Certificate $certificate): Certificate
    {
        $next = $certificate->signature_status->next();

        if ($next === null) {
            throw ValidationException::withMessages([
                'signature_status' => 'El certificado ya completó el flujo de firmas.',
            ]);
        }

        return $this->updateSignatureStatus($certificate, $next);
    }

    /**
     * @throws ValidationException
     */
    public function updateSignatureStatus(Certificate $certificate, SignatureStatus $status): Certificate
    {
        $previous = $certificate->signature_status;

        $payload = ['signature_status' => $status];

        if ($status === SignatureStatus::LISTO && $previous !== SignatureStatus::LISTO) {
            $payload['listo_en'] = now();
        }

        if ($status !== SignatureStatus::LISTO) {
            $payload['listo_en'] = null;
        }

        $certificate->update($payload);
        $certificate = $certificate->fresh(['preinscription', 'instructor', 'course']) ?? $certificate;

        if ($status === SignatureStatus::LISTO && $previous !== SignatureStatus::LISTO) {
            $certificate = $this->regeneratePdf($certificate);
            $this->notifyCertificateReady($certificate);
        }

        return $certificate;
    }

    public function notifyCertificateReady(Certificate $certificate): void
    {
        $certificate->loadMissing(['preinscription', 'instructor', 'course']);

        if ($certificate->isInstructorCertificate()) {
            $instructor = $certificate->instructor;
            if ($instructor !== null) {
                $instructor->notify(new CertificateReadyNotification($certificate));
            }

            return;
        }

        $email = $certificate->preinscription?->email;
        if (! filled($email)) {
            return;
        }

        $student = User::where('email', $email)->first();
        if ($student !== null) {
            $student->notify(new CertificateReadyNotification($certificate));

            return;
        }

        Notification::route('mail', $email)
            ->notify(new CertificateReadyNotification($certificate));
    }

    /**
     * HU-30: manually resend readiness notification.
     *
     * @throws ValidationException
     */
    public function resendReadyNotification(Certificate $certificate): void
    {
        if ($certificate->signature_status !== SignatureStatus::LISTO) {
            throw ValidationException::withMessages([
                'certificate' => 'Solo se puede reenviar la notificación cuando el certificado está listo.',
            ]);
        }

        $this->notifyCertificateReady($certificate);
    }

    /**
     * @return array{inicio: string|null, fin: string|null, rango: string}
     */
    public function courseDateRange(Course $course): array
    {
        $course->loadMissing('groups.sessions');

        $dates = $course->groups
            ->flatMap(fn ($group) => $group->sessions->pluck('fecha'))
            ->filter()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return [
                'inicio' => null,
                'fin' => null,
                'rango' => 'Fechas no registradas',
            ];
        }

        $inicio = $dates->first();
        $fin = $dates->last();

        return [
            'inicio' => $inicio->format('d/m/Y'),
            'fin' => $fin->format('d/m/Y'),
            'rango' => $inicio->equalTo($fin)
                ? $inicio->format('d/m/Y')
                : $inicio->format('d/m/Y').' – '.$fin->format('d/m/Y'),
        ];
    }

    public function generatePdf(Certificate $certificate)
    {
        if (! $this->isReady($certificate)) {
            throw ValidationException::withMessages([
                'certificate' => 'El certificado aún no está listo para descargar.',
            ]);
        }

        $certificate->loadMissing(['preinscription', 'instructor', 'course.instructor', 'course.groups.sessions']);

        $verifyUrl = $this->verificationUrl($certificate);
        $dates = $this->courseDateRange($certificate->course);
        $qrDataUri = $this->buildQrDataUri($verifyUrl);
        $logoDataUri = $this->buildLogoDataUri();

        $isInstructor = $certificate->isInstructorCertificate();

        return Pdf::loadView('pdf.certificado', [
            'certificado' => [
                'participante' => $certificate->beneficiaryName(),
                'curso' => $certificate->course?->nombre ?? 'Curso',
                'tipo' => $certificate->tipo?->getLabel() ?? 'Certificado',
                'es_instructor' => $isInstructor,
                'grupos' => $certificate->grupos_detalle ?: '—',
                'frase' => $isInstructor
                    ? 'Por haber dictado el curso de:'
                    : 'Por haber completado el curso de:',
                'carga_horaria' => $certificate->course?->carga_horaria
                    ? $certificate->course->carga_horaria.' horas académicas'
                    : 'No registrada',
                'fechas_curso' => $dates['rango'],
                'emitido_en' => $certificate->emitido_en?->format('d / m / Y') ?? now()->format('d / m / Y'),
                'codigo_unico' => $certificate->codigo_unico,
                'instructor' => $certificate->course?->instructor?->name ?? 'Docente',
                'url_verificacion' => $verifyUrl,
                'qr_data_uri' => $qrDataUri,
                'logo_data_uri' => $logoDataUri,
            ],
        ])->setPaper('a4', 'landscape');
    }

    public function download(Certificate $certificate): Response
    {
        if (! $this->isReady($certificate)) {
            throw ValidationException::withMessages([
                'certificate' => 'El certificado aún no está listo para descargar.',
            ]);
        }

        $filename = 'certificado-'.Str::slug($certificate->codigo_unico).'.pdf';

        $disk = $this->resolveStorageDisk($certificate);

        if ($disk === null) {
            $certificate = $this->regeneratePdf($certificate);
            $disk = $this->resolveStorageDisk($certificate);
        }

        if ($disk !== null && filled($certificate->pdf_path)) {
            try {
                return Storage::disk($disk)->download(
                    $certificate->pdf_path,
                    $filename,
                    ['Content-Type' => 'application/pdf']
                );
            } catch (\Throwable) {
                // Fallback to on-the-fly rendering if storage streaming fails
            }
        }

        return $this->generatePdf($certificate)->download($filename);
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
        $content = $pdf->output();

        $saved = false;
        try {
            $saved = (bool) Storage::disk('cloudinary')->put($path, $content, [
                'resource_type' => 'raw',
            ]);
        } catch (\Throwable) {
            $saved = false;
        }

        if (! $saved) {
            try {
                Storage::disk('local')->put($path, $content);
            } catch (\Throwable) {
                // Local fallback attempted
            }
        }

        $certificate->update(['pdf_path' => $path]);

        return $certificate->fresh() ?? $certificate;
    }

    private function resolveStorageDisk(Certificate $certificate): ?string
    {
        $path = $certificate->pdf_path;
        if (! filled($path)) {
            return null;
        }

        try {
            if (Storage::disk('cloudinary')->exists($path)) {
                return 'cloudinary';
            }
        } catch (\Throwable) {
            // Cloudinary disk unavailable
        }

        try {
            if (Storage::disk('local')->exists($path)) {
                return 'local';
            }
        } catch (\Throwable) {
            // Local disk unavailable
        }

        return null;
    }

    private function createInstructorCertificate(Course $course, string $gruposDetalle): Certificate
    {
        return Certificate::create([
            'preinscription_id' => null,
            'instructor_id' => $course->instructor_id,
            'course_id' => $course->id,
            'tipo' => CertificateType::INSTRUCTOR,
            'grupos_detalle' => $gruposDetalle,
            'codigo_unico' => $this->generateUniqueCode(),
            'signature_status' => SignatureStatus::PENDIENTE,
            'emitido_en' => now(),
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'CERT-'.now()->year.'-'.strtoupper(Str::random(8));
        } while (Certificate::where('codigo_unico', $code)->exists());

        return $code;
    }

    private function buildLogoDataUri(): ?string
    {
        $candidates = [
            public_path('images/umss-escudo.png'),
            base_path('resources/images/umss-escudo.png'),
        ];

        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }

            $bytes = file_get_contents($path);
            if ($bytes === false || $bytes === '') {
                continue;
            }

            return 'data:image/png;base64,'.base64_encode($bytes);
        }

        return null;
    }

    private function buildQrDataUri(string $verifyUrl): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        try {
            $qrCode = Encoder::encode($verifyUrl, ErrorCorrectionLevel::M());
            $matrix = $qrCode->getMatrix();
            $modules = $matrix->getWidth();
            $scale = 8;
            $margin = 2;
            $size = ($modules + ($margin * 2)) * $scale;

            $image = imagecreatetruecolor($size, $size);
            if ($image === false) {
                return null;
            }

            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 14, 46, 95);
            imagefilledrectangle($image, 0, 0, $size, $size, $white);

            for ($y = 0; $y < $modules; $y++) {
                for ($x = 0; $x < $modules; $x++) {
                    if (! $matrix->get($x, $y)) {
                        continue;
                    }

                    $x1 = ($x + $margin) * $scale;
                    $y1 = ($y + $margin) * $scale;
                    imagefilledrectangle($image, $x1, $y1, $x1 + $scale - 1, $y1 + $scale - 1, $black);
                }
            }

            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            imagedestroy($image);

            if ($png === false || $png === '') {
                return null;
            }

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable) {
            return null;
        }
    }
}
