<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'preinscription_id',
        'instructor_id',
        'course_id',
        'tipo',
        'grupos_detalle',
        'codigo_unico',
        'pdf_path',
        'es_escaneado',
        'signature_status',
        'emitido_en',
        'listo_en',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => CertificateType::class,
            'signature_status' => SignatureStatus::class,
            'es_escaneado' => 'boolean',
            'emitido_en' => 'datetime',
            'listo_en' => 'datetime',
        ];
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isInstructorCertificate(): bool
    {
        return $this->tipo === CertificateType::INSTRUCTOR;
    }

    public function beneficiaryName(): string
    {
        if ($this->isInstructorCertificate()) {
            return $this->instructor?->name ?? 'Instructor';
        }

        return $this->preinscription?->full_name ?? 'Participante';
    }

    public function administrativeDelayLabel(): string
    {
        $start = $this->emitido_en;
        $end = $this->listo_en ?? ($this->signature_status === SignatureStatus::LISTO ? now() : now());

        if ($start === null) {
            return '—';
        }

        if ($this->signature_status !== SignatureStatus::LISTO) {
            return $this->humanDiff($start, now()).' (en proceso)';
        }

        return $this->humanDiff($start, $end);
    }

    private function humanDiff(CarbonInterface $from, CarbonInterface $to): string
    {
        $days = (int) $from->diffInDays($to);

        if ($days === 0) {
            $hours = max(1, (int) $from->diffInHours($to));

            return $hours === 1 ? '1 hora' : "{$hours} horas";
        }

        return $days === 1 ? '1 día' : "{$days} días";
    }
}
