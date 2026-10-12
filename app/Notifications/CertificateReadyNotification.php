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

        $name = $notifiable->name
            ?? $this->certificate->beneficiaryName();

        $subject = $isInstructor
            ? 'Tu certificado de instructor está disponible — UMSS Formación Continua'
            : 'Tu certificado está disponible — UMSS Formación Continua';

        $line = $isInstructor
            ? 'Tu certificado digital como instructor ya está listo para descargar.'
            : 'Tu certificado digital ya está listo para descargar.';

        // Estudiantes no se auto-registran: descarga pública por código (sin login).
        // Usa APP_PUBLIC_URL (Render) cuando está configurado.
        $downloadUrl = $service->publicDownloadUrl($this->certificate);

        $actionUrl = $isInstructor
            ? $service->publicAbsoluteUrl('/instructor/certificates')
            : $downloadUrl;

        $actionLabel = $isInstructor ? 'Ir a Mis certificados' : 'Descargar certificado PDF';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola '.$name.',')
            ->line($line)
            ->line('Curso: '.($this->certificate->course?->nombre ?? 'Formación Continua'))
            ->line('Código: '.$this->certificate->codigo_unico)
            ->action($actionLabel, $actionUrl);

        if (! $isInstructor) {
            $message
                ->line('Enlace directo de descarga: '.$downloadUrl)
                ->line('Verificación pública: '.$verifyUrl);
        } else {
            $message->line('También puedes verificarlo públicamente: '.$verifyUrl);
        }

        return $message
            ->line('Para el certificado físico, acércate a la oficina de Formación Continua con tu documento de identidad.');
    }
}
