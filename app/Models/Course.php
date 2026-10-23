<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\GroupStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'contenido',
        'portada_path',
        'carga_horaria',
        'nivel',
        'periodo',
        'status',
        'motivo_cancelacion',
        'precio_umss',
        'precio_externo',
        'precio_auxiliar',
        'instructor_id',
        'attendance_weight',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'nivel' => CourseLevel::class,
            'carga_horaria' => 'string',
            'attendance_weight' => 'integer',
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

    /**
     * Oldest group that still accepts public preinscriptions.
     *
     * Uses the same rule the public preinscription page enforces (AGENTS.md §2):
     * enabled or pending-enablement groups, with free committed capacity.
     * Closed, in-progress and full groups are skipped so public links never
     * dead-end the visitor.
     */
    public function nextGroupOpenForPreinscription(): ?Group
    {
        return $this->groups()
            ->whereIn('status', [GroupStatus::HABILITADO, GroupStatus::NO_HABILITADO])
            ->withConfirmedCount()
            ->orderBy('id')
            ->get()
            ->first(fn (Group $group): bool => ! $group->esta_lleno);
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(EvaluationCriteria::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function courseInterests(): HasMany
    {
        return $this->hasMany(CourseInterest::class);
    }

    public function getPortadaUrlAttribute(): ?string
    {
        if (! $this->portada_path) {
            return null;
        }

        if (str_starts_with($this->portada_path, 'http://') || str_starts_with($this->portada_path, 'https://')) {
            return $this->portada_path;
        }

        return Storage::disk('cloudinary')->url($this->portada_path);
    }
}
