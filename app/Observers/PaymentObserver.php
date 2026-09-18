<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\PaymentConfirmedNotification;
use App\Models\Payment;
use App\Services\PreinscriptionService;
use Illuminate\Support\Facades\Mail;

class PaymentObserver
{
    public function __construct(
        protected PreinscriptionService $preinscriptionService,
    ) {}

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
        $preinscription = $payment->preinscription;

        $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

        $payment->loadMissing('preinscription.group.course');

        Mail::to($preinscription->email)
            ->queue(new PaymentConfirmedNotification($payment));
    }

    protected function handleRejected(Payment $payment): void
    {
        $preinscription = $payment->preinscription;

        if ($preinscription->status === PreinscriptionStatus::PENDIENTE_PAGO) {
            $preinscription->update(['status' => PreinscriptionStatus::RECHAZADO]);
        }
    }
}
