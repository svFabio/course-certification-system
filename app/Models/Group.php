<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
use App\Support\BusinessRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'nombre',
        'aula',
        'hora_inicio',
        'hora_fin',
        'cupo_minimo',
        'cupo_maximo',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Group $group): void {
            if ($group->hora_inicio === null || $group->course === null) {
                return;
            }

            $duration = BusinessRules::SESSION_DURATION_HOURS[$group->course->carga_horaria] ?? 1.5;

            $group->hora_fin = $group->hora_inicio->copy()->addMinutes((int) round($duration * 60));
        });
    }

    protected function casts(): array
    {
        return [
            'status' => GroupStatus::class,
            'hora_inicio' => 'datetime:H:i',
            'hora_fin' => 'datetime:H:i',
            'cupo_minimo' => 'integer',
            'cupo_maximo' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function preinscriptions(): HasMany
    {
        return $this->hasMany(Preinscription::class);
    }

    public function scopeWithConfirmedCount(Builder $query): Builder
    {
        return $query->withCount(['preinscriptions as confirmed_count' => fn ($q) => $q->whereIn('status', [
            PreinscriptionStatus::PENDIENTE_PAGO,
            PreinscriptionStatus::INSCRITO,
        ])]);
    }

    public function getCapacityPercentageAttribute(): float
    {
        if (! $this->cupo_maximo || $this->cupo_maximo === 0) {
            return 0.0;
        }

        $confirmed = $this->confirmed_count ?? $this->preinscriptions()
            ->whereIn('status', [
                PreinscriptionStatus::PENDIENTE_PAGO,
                PreinscriptionStatus::INSCRITO,
            ])
            ->count();

        return round(($confirmed / $this->cupo_maximo) * 100, 1);
    }

    public function getCuposOcupadosAttribute(): int
    {
        return $this->preinscriptions()
            ->whereIn('status', [
                PreinscriptionStatus::PENDIENTE_PAGO,
                PreinscriptionStatus::INSCRITO,
            ])
            ->count();
    }

    public function getCuposDisponiblesAttribute(): int
    {
        return max(0, $this->cupo_maximo - $this->cupos_ocupados);
    }

    public function getEstaLlenoAttribute(): bool
    {
        return $this->cupos_disponibles === 0;
    }
}
