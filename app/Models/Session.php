<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $table = 'course_sessions';

    protected $fillable = [
        'group_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'dictada',
        'es_pospuesta',
        'fecha_original',
        'motivo_reprogramacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_original' => 'date',
            'hora_inicio' => 'datetime:H:i',
            'hora_fin' => 'datetime:H:i',
            'dictada' => 'boolean',
            'es_pospuesta' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function isUpcoming(): bool
    {
        return $this->fecha->isFuture()
            || ($this->fecha->isToday() && $this->hora_inicio->format('H:i') > now()->format('H:i'));
    }

    public function isPast(): bool
    {
        return ! $this->isUpcoming();
    }
}
