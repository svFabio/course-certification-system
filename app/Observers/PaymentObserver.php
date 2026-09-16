<?php

declare(strict_types=1);

namespace App\Observers;

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
        if ($payment->wasChanged('verificado_por') && $payment->verificado_por) {
            $this->preinscriptionService->processPaymentConfirmation($payment->preinscription);

            Mail::to($payment->preinscription->email)
                ->send(new PaymentConfirmedNotification($payment));
        }
    }
}
