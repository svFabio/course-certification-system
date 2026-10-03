<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
    ) {
        $this->payment->loadMissing('preinscription.group.course');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pago rechazado — {$this->payment->preinscription->group->course->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
