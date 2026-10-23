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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

            $group->hora_fin = BusinessRules::calculateSessionEndTime(
                $group->hora_inicio,
                $group->course->carga_horaria
            );
        });

        static::deleting(function (Group $group): void {
            if ($group->preinscriptions()->exists()) {
                throw new \DomainException(
                    'No se puede eliminar un grupo que ya contiene preinscripciones o pagos registrados. '
                    .'Utilice la opción de "Fusión de Grupos" para transferir a los estudiantes o procese las devoluciones de caja correspondientes.'
                );
            }
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

    public function certificates(): HasManyThrough
    {
        return $this->hasManyThrough(Certificate::class, Preinscription::class);
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

        return round(($this->cupos_ocupados / $this->cupo_maximo) * 100, 1);
    }

    public function getCuposOcupadosAttribute(): int
    {
        $countedStatuses = [
            PreinscriptionStatus::PENDIENTE_PAGO,
            PreinscriptionStatus::INSCRITO,
        ];

        $confirmedCount = $this->attributes['confirmed_count'] ?? null;

        if ($confirmedCount !== null) {
            return (int) $confirmedCount;
        }

        // Reuse the eager-loaded relation when present so loops never fire a COUNT per row.
        if ($this->relationLoaded('preinscriptions')) {
            return $this->preinscriptions
                ->filter(fn (Preinscription $preinscription): bool => in_array($preinscription->status, $countedStatuses, true))
                ->count();
        }

        return (int) $this->preinscriptions()
            ->whereIn('status', $countedStatuses)
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
