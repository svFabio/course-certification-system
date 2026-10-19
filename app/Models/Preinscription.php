<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Preinscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'ci',
        'cod_sis',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'celular',
        'email',
        'tipo_participante',
        'status',
        'fotocopia_ci',
        'auxiliar_certificado_path',
        'auxiliar_certificado_aprobado',
        'auxiliar_certificado_aprobado_por',
        'auxiliar_certificado_aprobado_en',
        'auxiliar_certificado_motivo',
    ];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'status' => PreinscriptionStatus::class,
            'tipo_participante' => TipoParticipante::class,
            'fotocopia_ci' => 'boolean',
            'auxiliar_certificado_aprobado' => 'boolean',
            'auxiliar_certificado_aprobado_en' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function studentRequests(): HasMany
    {
        return $this->hasMany(StudentRequest::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function certificateApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auxiliar_certificado_aprobado_por');
    }

    protected function ci(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? mb_strtoupper(trim($value)) : null,
            set: fn (?string $value) => $value !== null ? mb_strtoupper(trim($value)) : null,
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellido_paterno.' '.($this->apellido_materno ?? ''));
    }

    public function isAuxiliar(): bool
    {
        return $this->tipo_participante === TipoParticipante::AUXILIAR
            || $this->tipo_participante === TipoParticipante::AUXILIAR->value;
    }

    public function hasApprovedAuxiliarCertificate(): bool
    {
        return $this->isAuxiliar() && $this->auxiliar_certificado_aprobado === true;
    }

    public function getPriceAttribute(): float
    {
        $course = $this->group?->course;
        if (! $course) {
            return 0.0;
        }

        $tipo = $this->tipo_participante instanceof TipoParticipante
            ? $this->tipo_participante
            : TipoParticipante::tryFrom((string) $this->tipo_participante);

        return match ($tipo) {
            TipoParticipante::UMSS => (float) $course->precio_umss,
            TipoParticipante::EXTERNO => (float) $course->precio_externo,
            TipoParticipante::AUXILIAR => (float) $course->precio_auxiliar,
            default => throw new \InvalidArgumentException('Invalid participant type: '.($this->tipo_participante instanceof TipoParticipante ? $this->tipo_participante->value : (string) $this->tipo_participante)),
        };
    }

    public function getChargeablePriceAttribute(): float
    {
        $course = $this->group?->course;
        if (! $course) {
            return 0.0;
        }

        $tipo = $this->tipo_participante instanceof TipoParticipante
            ? $this->tipo_participante
            : TipoParticipante::tryFrom((string) $this->tipo_participante);

        if ($tipo === TipoParticipante::AUXILIAR && ! $this->hasApprovedAuxiliarCertificate()) {
            return (float) $course->precio_umss;
        }

        return match ($tipo) {
            TipoParticipante::UMSS => (float) $course->precio_umss,
            TipoParticipante::EXTERNO => (float) $course->precio_externo,
            TipoParticipante::AUXILIAR => (float) $course->precio_auxiliar,
            default => throw new \InvalidArgumentException('Invalid participant type: '.($this->tipo_participante instanceof TipoParticipante ? $this->tipo_participante->value : (string) $this->tipo_participante)),
        };
    }
}
