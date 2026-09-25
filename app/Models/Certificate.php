<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateType;
use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'preinscription_id',
        'course_id',
        'tipo',
        'codigo_unico',
        'pdf_path',
        'signature_status',
        'emitido_en',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => CertificateType::class,
            'signature_status' => SignatureStatus::class,
            'emitido_en' => 'datetime',
        ];
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
