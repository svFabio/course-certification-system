<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreinscriptionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly array $preinscriptionData)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Preinscripción aprobada')
            ->line('Su preinscripción ha sido aprobada.')
            ->line('Curso: ' . $this->preinscriptionData['curso_nombre'])
            ->line('Grupo: ' . $this->preinscriptionData['grupo_nombre'])
            ->action('Realizar pago', route('payments.create', $this->preinscriptionData['preinscription_id']));
    }
}
