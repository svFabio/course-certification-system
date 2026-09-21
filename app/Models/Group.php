<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GroupStatus;
use App\Enums\PreinscriptionStatus;
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
        'hora_inicio',
        'hora_fin',
        'cupo_minimo',
        'cupo_maximo',
        'status',
    ];

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
        return $query->withCount(['preinscriptions as confirmed_count' => fn ($q) => $q->where('status', PreinscriptionStatus::INSCRITO)]);
    }

    public function getCapacityPercentageAttribute(): float
    {
        if (! $this->cupo_maximo || $this->cupo_maximo === 0) {
            return 0.0;
        }

        $confirmed = $this->confirmed_count ?? $this->preinscriptions()
            ->where('status', PreinscriptionStatus::INSCRITO)
            ->count();

        return round(($confirmed / $this->cupo_maximo) * 100, 1);
    }
}
