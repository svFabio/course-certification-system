<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateType;
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
        'emitido_en',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => CertificateType::class,
            'emitido_en' => 'datetime',
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

    public function isReady(): bool
    {
        return filled($this->pdf_path);
    }

    public function beneficiaryName(): string
    {
        if ($this->isInstructorCertificate()) {
            return $this->instructor?->name ?? 'Instructor';
        }

        return $this->preinscription?->full_name ?? 'Participante';
    }
}
