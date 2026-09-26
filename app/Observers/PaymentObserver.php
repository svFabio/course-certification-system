<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->syncPreinscriptionFromPayment($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('estado')) {
            $this->syncPreinscriptionFromPayment($payment);
        }
    }

    protected function syncPreinscriptionFromPayment(Payment $payment): void
    {
        match ($payment->estado) {
            PaymentStatus::VERIFICADO => $this->handleVerified($payment),
            PaymentStatus::RECHAZADO => $this->handleRejected($payment),
            default => null,
        };
    }

    protected function handleVerified(Payment $payment): void
    {
        $payment->loadMissing('preinscription.group.course');
        $preinscription = $payment->preinscription;

        // AGENTS §2: PENDIENTE_PAGO -> INSCRITO is the only legal promotion.
        // From any other source state the preinscription is left untouched (no
        // throw): the payment may legitimately be verificado while the
        // preinscription is retirada, rechazada or inside a refund flow, and
        // those transitions are owned by PaymentService.
        if ($preinscription->status !== PreinscriptionStatus::PENDIENTE_PAGO) {
            Log::debug('payment.verified.skipped_preinscription_transition', [
                'payment_id' => $payment->id,
                'preinscription_id' => $preinscription->id,
                'preinscription_status' => $preinscription->status->value,
            ]);

            return;
        }

        $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

        Mail::to($preinscription->email)
            ->queue(new PaymentConfirmedNotification($payment));
    }

    protected function handleRejected(Payment $payment): void
    {
        $payment->loadMissing('preinscription');
        $preinscription = $payment->preinscription;
        $status = $preinscription->status;

        if ($status === PreinscriptionStatus::PENDIENTE_PAGO) {
            $preinscription->update(['status' => PreinscriptionStatus::RECHAZADO]);

            return;
        }

        // An inscrito participant only loses the seat when this rejected
        // payment was the one gating the enrollment (AGENTS §2: rejecting a
        // payment frees the group seat).
        if ($status === PreinscriptionStatus::INSCRITO && ! $this->hasOtherVerifiedPayment($payment)) {
            $preinscription->update(['status' => PreinscriptionStatus::RECHAZADO]);
        }

        // Terminal / refund states (retirado, rechazado, devolución pendiente,
        // reembolsado) are a no-op so the flow stays idempotent.
    }

    protected function hasOtherVerifiedPayment(Payment $payment): bool
    {
        return Payment::query()
            ->where('preinscription_id', $payment->preinscription_id)
            ->whereKeyNot($payment->getKey())
            ->where('estado', PaymentStatus::VERIFICADO->value)
            ->exists();
    }
}
