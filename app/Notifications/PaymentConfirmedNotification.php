<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly array $paymentData)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago confirmado')
            ->line('Su pago ha sido confirmado. Ya está inscrito al curso.')
            ->line('Monto: Bs. ' . number_format($this->paymentData['monto'], 2))
            ->action('Ver mi inscripción', route('preinscriptions.show', $this->paymentData['preinscription_id']));
    }
}
