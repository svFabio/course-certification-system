<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        match ($payment->estado) {
            PaymentStatus::VERIFICADO => $this->handleVerified($payment),
            PaymentStatus::RECHAZADO => $this->handleRejected($payment),
            default => null,
        };
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('estado')) {
            match ($payment->estado) {
                PaymentStatus::VERIFICADO => $this->handleVerified($payment),
                PaymentStatus::RECHAZADO => $this->handleRejected($payment),
                default => null,
            };
        }
    }

    protected function handleVerified(Payment $payment): void
    {
        $payment->loadMissing('preinscription.group.course');
        $preinscription = $payment->preinscription;

        if ($preinscription->status !== PreinscriptionStatus::INSCRITO) {
            $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

            Mail::to($preinscription->email)
                ->queue(new PaymentConfirmedNotification($payment));
        }
    }

    protected function handleRejected(Payment $payment): void
    {
        $payment->loadMissing('preinscription');
        $preinscription = $payment->preinscription;

        if ($preinscription->status === PreinscriptionStatus::PENDIENTE_PAGO) {
            $preinscription->update(['status' => PreinscriptionStatus::RECHAZADO]);
        }
    }
}
