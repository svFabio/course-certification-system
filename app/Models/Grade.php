<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_criteria_id',
        'preinscription_id',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'float',
        ];
    }

    public function evaluationCriteria(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriteria::class);
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }
}
