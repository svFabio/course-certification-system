<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'preinscription_id',
        'monto',
        'metodo',
        'estado',
        'numero_comprobante',
        'verificado_por',
        'verificado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'metodo' => PaymentMethod::class,
            'verificado_en' => 'datetime',
        ];
    }

    public function preinscription(): BelongsTo
    {
        return $this->belongsTo(Preinscription::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}
