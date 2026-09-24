<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupMergedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $courseNombre;

    public string $originNombre;

    public string $destinationNombre;

    public string $studentNombres;

    public function __construct(
        public Group $origin,
        public Group $destination,
        public Preinscription $preinscription,
    ) {
        $this->destination->loadMissing('course');
        $this->courseNombre = (string) ($this->destination->course?->nombre ?? '');
        $this->originNombre = (string) $this->origin->nombre;
        $this->destinationNombre = (string) $this->destination->nombre;
        $this->studentNombres = (string) $this->preinscription->nombres;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Preinscripción trasladada — {$this->courseNombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.group-merged',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
