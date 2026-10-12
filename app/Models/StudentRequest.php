<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentRequestStatus;
use App\Enums\StudentRequestType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'preinscription_id',
        'tipo',
        'group_id_destino',
        'motivo',
        'estado',
        'motivo_rechazo',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'preinscription_id' => 'integer',
            'tipo' => StudentRequestType::class,
            'group_id_destino' => 'integer',
            'estado' => StudentRequestStatus::class,
            'resolved_by' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }

    public function destinationGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id_destino');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('estado', StudentRequestStatus::PENDIENTE->value);
    }
}
