<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'preinscription_id',
        'status',
        'lat',
        'lng',
        'distancia_metros',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'distancia_metros' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }
}
