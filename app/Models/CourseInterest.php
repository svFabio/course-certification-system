<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'email',
        'user_id',
        'estado',
        'notificado_at',
        'preinscription_id',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'user_id' => 'integer',
            'estado' => InterestStatus::class,
            'notificado_at' => 'datetime',
            'preinscription_id' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            InterestStatus::INTERESADO->value,
            InterestStatus::NOTIFICADO->value,
        ]);
    }
}
