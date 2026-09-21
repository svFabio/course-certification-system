<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'fecha',
        'alcance',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'activo' => 'boolean',
        ];
    }
}
