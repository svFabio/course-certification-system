<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Preinscription;
use App\Support\BusinessRules;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function registerAndVerify(Preinscription $preinscription, string $method, ?string $reference, bool $fotocopiaCi = false): Payment
    {
        if ($fotocopiaCi) {
            $preinscription->update(['fotocopia_ci' => true]);
        }

        return Payment::create([
            'preinscription_id' => $preinscription->id,
            'monto' => $preinscription->price,
            'metodo' => $method,
            'numero_comprobante' => $reference,
            'estado' => PaymentStatus::VERIFICADO,
            'verificado_por' => auth()->id(),
            'verificado_en' => now(),
        ]);
    }

    public function verify(Payment $payment): void
    {
        $payment->update([
            'verificado_por' => auth()->id(),
            'verificado_en' => now(),
            'estado' => PaymentStatus::VERIFICADO,
            'motivo_rechazo' => null,
        ]);
    }

    public function reject(Payment $payment, string $reason): void
    {
        $payment->update([
            'estado' => PaymentStatus::RECHAZADO,
            'motivo_rechazo' => $reason,
            'verificado_por' => null,
            'verificado_en' => null,
        ]);
    }

    public function verifyAmount(int $preinscriptionId, float $amount): void
    {
        $preinscription = Preinscription::with('group.course')->findOrFail($preinscriptionId);

        $expected = BusinessRules::calculatePrice(
            (int) $preinscription->group->course->carga_horaria,
            $preinscription->tipo_participante
        );

        if ($amount !== $expected) {
            throw ValidationException::withMessages([
                'monto' => "El monto correcto para este participante es Bs. {$expected}",
            ]);
        }
    }
}
