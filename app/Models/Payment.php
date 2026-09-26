<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        'motivo_rechazo',
        'motivo_devolucion',
        'numero_comprobante',
        'verificado_por',
        'verificado_en',
        'reembolsado_por',
        'reembolsado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'metodo' => PaymentMethod::class,
            'estado' => PaymentStatus::class,
            'verificado_en' => 'datetime',
            'reembolsado_en' => 'datetime',
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

    public function reimbursementApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reembolsado_por');
    }
}
