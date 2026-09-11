<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'contenido',
        'carga_horaria',
        'nivel',
        'periodo',
        'status',
        'precio_umss',
        'precio_externo',
        'precio_auxiliar',
        'instructor_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'carga_horaria' => 'string',
            'precio_umss' => 'decimal:2',
            'precio_externo' => 'decimal:2',
            'precio_auxiliar' => 'decimal:2',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
