<?php

declare(strict_types=1);

namespace App\Observers;

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
                'verificado' => $this->handleVerified($payment),
                'rechazado' => $this->handleRejected($payment),
                default => null,
            };
        }
    }

    protected function handleVerified(Payment $payment): void
    {
        $preinscription = $payment->preinscription;

        $preinscription->update(['status' => PreinscriptionStatus::INSCRITO]);

        Mail::queue(new PaymentConfirmedNotification($payment));
    }

    protected function handleRejected(Payment $payment): void
    {
        $preinscription = $payment->preinscription;

        if ($preinscription->status === PreinscriptionStatus::PENDIENTE_PAGO) {
            $preinscription->update(['status' => PreinscriptionStatus::RECHAZADO]);
        }
    }
}
