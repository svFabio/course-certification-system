<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\StudentRequestStatus;
use App\Models\StudentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentRequestResolvedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudentRequest $studentRequest,
    ) {
        $this->studentRequest->loadMissing(['preinscription.group.course', 'destinationGroup']);
    }

    public function envelope(): Envelope
    {
        $courseNombre = $this->studentRequest->preinscription?->group?->course?->nombre;

        $subject = match ($this->studentRequest->estado) {
            StudentRequestStatus::APROBADA => 'Solicitud aprobada',
            StudentRequestStatus::RECHAZADA => 'Solicitud rechazada',
            default => 'Solicitud actualizada',
        };

        return new Envelope(
            subject: filled($courseNombre) ? "{$subject} — {$courseNombre}" : $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-request-resolved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
