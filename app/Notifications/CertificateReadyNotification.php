<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Certificate $certificate) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $service = app(CertificateService::class);
        $verifyUrl = $service->verificationUrl($this->certificate);
        $isInstructor = $this->certificate->isInstructorCertificate();

        $downloadUrl = $isInstructor
            ? url('/instructor/certificates')
            : url('/estudiante/certificates');

        $name = $notifiable->name
            ?? $this->certificate->beneficiaryName();

        $subject = $isInstructor
            ? 'Tu certificado de instructor está disponible — UMSS Formación Continua'
            : 'Tu certificado está disponible — UMSS Formación Continua';

        $line = $isInstructor
            ? 'Tu certificado digital como instructor ya está listo para descargar.'
            : 'Tu certificado digital ya está listo para descargar.';

        $actionLabel = $isInstructor ? 'Ir a Mis certificados' : 'Ir a Mis certificados';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hola '.$name.',')
            ->line($line)
            ->line('Curso: '.($this->certificate->course?->nombre ?? 'Formación Continua'))
            ->line('Código: '.$this->certificate->codigo_unico)
            ->action($actionLabel, $downloadUrl)
            ->line('También puedes verificarlo públicamente: '.$verifyUrl)
            ->line('Para el certificado físico, acércate a la oficina de Formación Continua con tu documento de identidad.');
    }
}
