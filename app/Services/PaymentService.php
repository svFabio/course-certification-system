<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Enums\TipoParticipante;
use App\Events\GroupSheetsNeedRefresh;
use App\Models\Payment;
use App\Models\Preinscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function registerAndVerify(Preinscription $preinscription, string $method, ?string $reference, bool $fotocopiaCi = false): Payment
    {
        $this->assertAuxiliarCertificateApproved($preinscription);

        if ($fotocopiaCi) {
            $preinscription->update(['fotocopia_ci' => true]);
        }

        $payment = Payment::create([
            'preinscription_id' => $preinscription->id,
            'monto' => $preinscription->chargeable_price,
            'metodo' => $method,
            'numero_comprobante' => $reference,
            'estado' => PaymentStatus::VERIFICADO,
            'verificado_por' => auth()->id(),
            'verificado_en' => now(),
        ]);

        if ($preinscription->group_id !== null) {
            GroupSheetsNeedRefresh::dispatch($preinscription->group_id);
        }

        return $payment;
    }

    public function verify(Payment $payment): void
    {
        $payment->loadMissing('preinscription');

        $this->assertAuxiliarCertificateApproved($payment->preinscription);

        $payment->update([
            'verificado_por' => auth()->id(),
            'verificado_en' => now(),
            'estado' => PaymentStatus::VERIFICADO,
            'motivo_rechazo' => null,
        ]);

        $payment->loadMissing('preinscription.group');

        if ($payment->preinscription?->group_id !== null) {
            GroupSheetsNeedRefresh::dispatch($payment->preinscription->group_id);
        }
    }

    public function reject(Payment $payment, string $reason): void
    {
        $payment->update([
            'estado' => PaymentStatus::RECHAZADO,
            'motivo_rechazo' => $reason,
            'verificado_por' => null,
            'verificado_en' => null,
        ]);

        $payment->loadMissing('preinscription.group');

        if ($payment->preinscription?->group_id !== null) {
            GroupSheetsNeedRefresh::dispatch($payment->preinscription->group_id);
        }
    }

    public function verifyAmount(int $preinscriptionId, float $amount): void
    {
        $preinscription = Preinscription::with('group.course')->findOrFail($preinscriptionId);

        $expected = $preinscription->chargeable_price;

        if (abs($amount - $expected) > 0.005) {
            throw ValidationException::withMessages([
                'monto' => "El monto correcto para este participante es Bs. {$expected}",
            ]);
        }
    }

    public function requestRefund(Preinscription $preinscription, string $reason): void
    {
        $preinscription->refresh();

        $this->assertRefundable($preinscription);

        DB::transaction(function () use ($preinscription, $reason) {
            $preinscription->update([
                'status' => PreinscriptionStatus::DEVOLUCION_PENDIENTE,
            ]);

            $preinscription->payments()
                ->where('estado', PaymentStatus::VERIFICADO->value)
                ->update([
                    'estado' => PaymentStatus::DEVOLUCION_PENDIENTE->value,
                    'motivo_rechazo' => null,
                    'motivo_devolucion' => $reason,
                ]);

            if ($preinscription->group_id !== null) {
                GroupSheetsNeedRefresh::dispatch($preinscription->group_id);
            }
        });
    }

    public function confirmRefund(Preinscription $preinscription): void
    {
        $preinscription->refresh();

        if ($preinscription->status !== PreinscriptionStatus::DEVOLUCION_PENDIENTE) {
            throw ValidationException::withMessages([
                'status' => 'La preinscripción debe estar en estado "devolución pendiente" para confirmar el reembolso.',
            ]);
        }

        DB::transaction(function () use ($preinscription) {
            $preinscription->update([
                'status' => PreinscriptionStatus::REEMBOLSADO,
            ]);

            $preinscription->payments()
                ->where('estado', PaymentStatus::DEVOLUCION_PENDIENTE->value)
                ->update([
                    'estado' => PaymentStatus::REEMBOLSADO->value,
                    'reembolsado_por' => auth()->id(),
                    'reembolsado_en' => now(),
                ]);

            if ($preinscription->group_id !== null) {
                GroupSheetsNeedRefresh::dispatch($preinscription->group_id);
            }
        });
    }

    public function cancelRefundRequest(Preinscription $preinscription): void
    {
        $preinscription->refresh();

        if ($preinscription->status !== PreinscriptionStatus::DEVOLUCION_PENDIENTE) {
            throw ValidationException::withMessages([
                'status' => 'La preinscripción no tiene una devolución pendiente que cancelar.',
            ]);
        }

        DB::transaction(function () use ($preinscription) {
            $preinscription->update([
                'status' => PreinscriptionStatus::INSCRITO,
            ]);

            $preinscription->payments()
                ->where('estado', PaymentStatus::DEVOLUCION_PENDIENTE->value)
                ->update([
                    'estado' => PaymentStatus::VERIFICADO->value,
                    'motivo_devolucion' => null,
                ]);

            if ($preinscription->group_id !== null) {
                GroupSheetsNeedRefresh::dispatch($preinscription->group_id);
            }
        });
    }

    protected function assertAuxiliarCertificateApproved(Preinscription $preinscription): void
    {
        if ($preinscription->tipo_participante === TipoParticipante::AUXILIAR->value && ! $preinscription->hasApprovedAuxiliarCertificate()) {
            throw ValidationException::withMessages([
                'auxiliar_certificado' => 'El 50% de descuento para participantes auxiliares requiere que el certificado emitido por Jefatura sea aprobado por el administrador antes de verificar el pago.',
            ]);
        }
    }

    protected function assertRefundable(Preinscription $preinscription): void
    {
        if ($preinscription->status !== PreinscriptionStatus::INSCRITO) {
            throw ValidationException::withMessages([
                'status' => 'Solo se puede solicitar una devolución para participantes inscritos.',
            ]);
        }
    }
}
