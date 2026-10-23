<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CertificateLifecycleStatus;
use App\Enums\CertificateType;
use App\Enums\PreinscriptionStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\CertificateReadyNotification;
use App\Support\BusinessRules;
use App\Support\DesignTokens;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

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

        $certificate = Certificate::create([
            'preinscription_id' => $preinscription->id,
            'course_id' => $preinscription->group->course_id,
            'tipo' => $type,
            'codigo_unico' => $this->generateUniqueCode(),
            'estado' => CertificateLifecycleStatus::GENERADO,
            'emitido_en' => now(),
        ]);

        $certificate = $this->regeneratePdf($certificate);
        $this->notifyCertificateReady($certificate);

        return $certificate;
    }

    /**
     * @return Collection<int, Certificate>
     */
    public function generateForCourse(Course $course): Collection
    {
        $course->loadMissing([
            'groups.preinscriptions.grades.evaluationCriteria',
            'groups.preinscriptions.attendances',
            'groups.preinscriptions.certificate',
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

                    if ($preinscription->relationLoaded('certificate') ? $preinscription->certificate !== null : $preinscription->certificate()->exists()) {
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
        return filled($certificate->pdf_path);
    }

    public function verificationUrl(Certificate $certificate): string
    {
        return $this->publicAbsoluteUrl(
            route('certificado.verificar', ['codigo' => $certificate->codigo_unico], absolute: false)
        );
    }

    public function publicDownloadUrl(Certificate $certificate): string
    {
        return $this->publicAbsoluteUrl(
            route('certificado.publico.descargar', ['codigo' => $certificate->codigo_unico], absolute: false)
        );
    }

    /**
     * Build an absolute URL using APP_PUBLIC_URL when set (e.g. Render), else APP_URL.
     */
    public function publicAbsoluteUrl(string $path): string
    {
        $base = rtrim((string) config('app.public_url', config('app.url')), '/');
        $path = '/'.ltrim($path, '/');

        return $base.$path;
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
     * Resend certificate availability notification.
     */
    public function resendReadyNotification(Certificate $certificate): void
    {
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
        $certificate->loadMissing(['preinscription', 'instructor', 'course.instructor', 'course.groups.sessions']);

        $verifyUrl = $this->verificationUrl($certificate);
        $dates = $this->courseDateRange($certificate->course);
        $qrDataUri = $this->buildQrDataUri($verifyUrl);
        $logoDataUri = $this->buildLogoDataUri();

        $isInstructor = $certificate->isInstructorCertificate();

        $directorNombre = (string) SystemSetting::get('director_academico_nombre', '');
        $directorCargo = (string) SystemSetting::get('director_academico_cargo', 'Director Académico');

        $jefeNombre = (string) SystemSetting::get('jefe_departamento_nombre', '');
        $jefeCargo = (string) SystemSetting::get('jefe_departamento_cargo', 'Jefe de Departamento');

        $instructorUser = $certificate->course?->instructor;
        $instructorNombre = $instructorUser?->name ?? 'Docente';

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
                'instructor' => $instructorNombre,
                'director_nombre' => $directorNombre,
                'director_cargo' => $directorCargo,
                'jefe_nombre' => $jefeNombre,
                'jefe_cargo' => $jefeCargo,
                'url_verificacion' => $verifyUrl,
                'qr_data_uri' => $qrDataUri,
                'logo_data_uri' => $logoDataUri,
            ],
        ])->setPaper('a4', 'landscape');
    }

    public function download(Certificate $certificate): Response
    {
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
        $pdf = $this->generatePdf($certificate);
        $path = 'certificates/'.$certificate->codigo_unico.'.pdf';
        $content = $pdf->output();

        try {
            $saved = (bool) Storage::disk('cloudinary')->put($path, $content, [
                'resource_type' => 'raw',
            ]);
            if ($saved) {
                $certificate->update(['pdf_path' => $path]);
            }
        } catch (\Throwable) {
            // Cloudinary write error handled gracefully
        }

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
        $certificate = Certificate::create([
            'preinscription_id' => null,
            'instructor_id' => $course->instructor_id,
            'course_id' => $course->id,
            'tipo' => CertificateType::INSTRUCTOR,
            'grupos_detalle' => $gruposDetalle,
            'codigo_unico' => $this->generateUniqueCode(),
            'estado' => CertificateLifecycleStatus::GENERADO,
            'emitido_en' => now(),
        ]);

        $certificate = $this->regeneratePdf($certificate);
        $this->notifyCertificateReady($certificate);

        return $certificate;
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

            [$wR, $wG, $wB] = DesignTokens::hexToRgb(DesignTokens::WHITE);
            [$bR, $bG, $bB] = DesignTokens::hexToRgb(DesignTokens::NAVY);

            $white = imagecolorallocate($image, $wR, $wG, $wB);
            $black = imagecolorallocate($image, $bR, $bG, $bB);
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

    public function downloadCourseCertificatesZip(Course $course): BinaryFileResponse
    {
        $certificates = $course->certificates()->with(['preinscription', 'instructor'])->get();

        if ($certificates->isEmpty()) {
            throw new \RuntimeException('El curso no tiene certificados emitidos para descargar.');
        }

        $zipName = 'certificados-curso-'.Str::slug($course->nombre).'.zip';

        return $this->createCertificatesZip($certificates, $zipName);
    }

    public function downloadGroupCertificatesZip(Group $group): BinaryFileResponse
    {
        $group->loadMissing('course');
        $certificates = $group->certificates()->with(['preinscription', 'instructor'])->get();

        if ($certificates->isEmpty()) {
            throw new \RuntimeException('El grupo no tiene certificados emitidos para descargar.');
        }

        $courseSlug = Str::slug($group->course->nombre ?? 'curso');
        $groupSlug = Str::slug($group->nombre);
        $zipName = "certificados-{$courseSlug}-{$groupSlug}.zip";

        return $this->createCertificatesZip($certificates, $zipName);
    }

    public function createCertificatesZip(Collection $certificates, string $zipName): BinaryFileResponse
    {
        $tempDir = $this->resolveWritableTempDirectory();
        $zipPath = $tempDir.DIRECTORY_SEPARATOR.uniqid('certs_', true).'.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo inicializar el archivo ZIP.');
        }

        foreach ($certificates as $cert) {
            $content = $this->getCertificatePdfContent($cert);
            $cleanName = Str::slug($cert->beneficiaryName());
            $filename = "{$cleanName}-{$cert->codigo_unico}.pdf";
            $zip->addFromString($filename, $content);
        }

        if ($zip->close() !== true) {
            @unlink($zipPath);
            throw new \RuntimeException('No se pudo finalizar el archivo ZIP (permisos del directorio temporal).');
        }

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function resolveWritableTempDirectory(): string
    {
        $candidates = [
            storage_path('app/temp'),
            storage_path('app'),
            sys_get_temp_dir(),
        ];

        foreach ($candidates as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            if (! is_dir($dir)) {
                continue;
            }

            @chmod($dir, 0775);

            if (! is_writable($dir)) {
                continue;
            }

            $probe = $dir.DIRECTORY_SEPARATOR.'.write_probe_'.uniqid('', true);
            if (@file_put_contents($probe, 'ok') === false) {
                continue;
            }
            @unlink($probe);

            return $dir;
        }

        throw new \RuntimeException('No hay un directorio temporal escribible para generar el ZIP.');
    }

    public function getCertificatePdfContent(Certificate $certificate): string
    {
        $disk = $this->resolveStorageDisk($certificate);

        if ($disk !== null && filled($certificate->pdf_path)) {
            try {
                $content = Storage::disk($disk)->get($certificate->pdf_path);
                if (filled($content)) {
                    return $content;
                }
            } catch (\Throwable) {
                // Fallback to regeneration
            }
        }

        $certificate = $this->regeneratePdf($certificate);
        $disk = $this->resolveStorageDisk($certificate);
        if ($disk !== null && filled($certificate->pdf_path)) {
            try {
                $content = Storage::disk($disk)->get($certificate->pdf_path);
                if (filled($content)) {
                    return $content;
                }
            } catch (\Throwable) {
                // Fallback to on-the-fly rendering
            }
        }

        return $this->generatePdf($certificate)->output();
    }

    /**
     * Admin uploads (or replaces) the physically signed & scanned certificate.
     * Accepts a Cloudinary/local storage path (Filament FileUpload) or an UploadedFile.
     *
     * @throws ValidationException
     */
    public function attachScannedCopy(Certificate $certificate, UploadedFile|string $file): Certificate
    {
        if ($certificate->isInstructorCertificate()) {
            throw ValidationException::withMessages([
                'archivo' => 'Solo se digitalizan certificados de estudiantes.',
            ]);
        }

        $path = $this->storeScannedFile($certificate, $file);
        $previousPath = $certificate->pdf_escaneado_path;

        $certificate->update([
            'pdf_escaneado_path' => $path,
            'escaneado_en' => now(),
            'estado' => CertificateLifecycleStatus::DIGITALIZADO,
        ]);

        if (filled($previousPath) && $previousPath !== $path) {
            $this->deleteStoredFile($previousPath);
        }

        return $certificate->fresh() ?? $certificate;
    }

    /**
     * Mark that a physical, printed certificate was handed over to the student.
     *
     * @throws ValidationException
     */
    public function markAsDelivered(Certificate $certificate): Certificate
    {
        if ($certificate->isInstructorCertificate()) {
            throw ValidationException::withMessages([
                'certificate' => 'Solo se marca entrega física de certificados de estudiantes.',
            ]);
        }

        if ($certificate->estado === CertificateLifecycleStatus::DIGITALIZADO) {
            throw ValidationException::withMessages([
                'certificate' => 'El certificado ya está digitalizado; no es necesario marcar entrega.',
            ]);
        }

        $certificate->update([
            'entregado_en' => now(),
            'estado' => CertificateLifecycleStatus::ENTREGADO,
        ]);

        return $certificate->fresh() ?? $certificate;
    }

    /**
     * Stream or download the digitized/scanned certificate document.
     *
     * @throws ValidationException
     */
    public function downloadScanned(Certificate $certificate, bool $inline = false): Response
    {
        if (! $certificate->hasScannedCopy()) {
            throw ValidationException::withMessages([
                'certificate' => 'Este certificado aún no tiene una copia digitalizada.',
            ]);
        }

        $path = $certificate->pdf_escaneado_path;
        $disk = $this->resolveDiskForPath($path);
        $extension = pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = 'certificado-digital-'.Str::slug($certificate->codigo_unico).'.'.$extension;

        if ($disk === null) {
            throw ValidationException::withMessages([
                'certificate' => 'No se encontró el archivo digitalizado en el almacenamiento.',
            ]);
        }

        $mime = match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        if ($inline) {
            return Storage::disk($disk)->response($path, $filename, ['Content-Type' => $mime]);
        }

        return Storage::disk($disk)->download($path, $filename, ['Content-Type' => $mime]);
    }

    /**
     * Store the scanned file in cloud (Cloudinary) storage.
     *
     * @throws ValidationException
     */
    private function storeScannedFile(Certificate $certificate, UploadedFile|string $file): string
    {
        if (is_string($file)) {
            if (! filled($file)) {
                throw ValidationException::withMessages([
                    'archivo' => 'Debe seleccionar un archivo PDF, JPG, PNG o WebP.',
                ]);
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
                throw ValidationException::withMessages([
                    'archivo' => 'El archivo debe ser PDF, JPG, PNG o WebP.',
                ]);
            }

            if ($this->resolveDiskForPath($file) === null) {
                throw ValidationException::withMessages([
                    'archivo' => 'No se encontró el archivo subido en el almacenamiento.',
                ]);
            }

            return $file;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        if (! in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo debe ser PDF, JPG, PNG o WebP.',
            ]);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no debe superar 10 MB.',
            ]);
        }

        $path = 'certificates/scanned/'.$certificate->codigo_unico.'.'.$extension;
        $options = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            ? ['resource_type' => 'image']
            : ['resource_type' => 'raw'];

        $saved = false;
        try {
            $saved = (bool) Storage::disk('cloudinary')->putFileAs(
                'certificates/scanned',
                $file,
                $certificate->codigo_unico.'.'.$extension,
                $options
            );
        } catch (\Throwable) {
            $saved = false;
        }

        if (! $saved) {
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo guardar el archivo escaneado en Cloudinary.',
            ]);
        }

        return $path;
    }

    private function deleteStoredFile(string $path): void
    {
        foreach (['cloudinary', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable) {
                // ignore cleanup failures
            }
        }
    }

    private function resolveDiskForPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        try {
            if (Storage::disk('cloudinary')->exists($path)) {
                return 'cloudinary';
            }
        } catch (\Throwable) {
            // Cloudinary unavailable
        }

        try {
            if (Storage::disk('local')->exists($path)) {
                return 'local';
            }
        } catch (\Throwable) {
            // Local unavailable
        }

        return null;
    }
}
