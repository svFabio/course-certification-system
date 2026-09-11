<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PreinscriptionStatus;
use App\Support\BusinessRules;
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
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'celular',
        'email',
        'tipo_participante',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PreinscriptionStatus::class,
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

    public function getFullNameAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellido_paterno . ' ' . ($this->apellido_materno ?? ''));
    }

    public function getPriceAttribute(): float
    {
        $courseHours = (int) $this->group->course->carga_horaria;

        return BusinessRules::calculatePrice($courseHours, $this->tipo_participante);
    }
}
