<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\StudentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentRequestReceivedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudentRequest $studentRequest,
    ) {
        $this->studentRequest->loadMissing('preinscription.group.course');
    }

    public function envelope(): Envelope
    {
        $courseNombre = $this->studentRequest->preinscription?->group?->course?->nombre;

        return new Envelope(
            subject: filled($courseNombre) ? "Solicitud recibida — {$courseNombre}" : 'Solicitud recibida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-request-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
