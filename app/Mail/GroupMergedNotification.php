<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\MergeParticipantDecision;
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

    public MergeParticipantDecision $decision;

    public function __construct(
        public Group $origin,
        public Group $destination,
        public Preinscription $preinscription,
        ?MergeParticipantDecision $decision = null,
    ) {
        $this->decision = $decision ?? MergeParticipantDecision::TRASLADAR;
        $this->destination->loadMissing('course');
        $this->courseNombre = (string) ($this->destination->course?->nombre ?? '');
        $this->originNombre = (string) $this->origin->nombre;
        $this->destinationNombre = (string) $this->destination->nombre;
        $this->studentNombres = (string) $this->preinscription->nombres;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            MergeParticipantDecision::TRASLADAR => "Preinscripción trasladada — {$this->courseNombre}",
            MergeParticipantDecision::REEMBOLSO => "Devolución por incompatibilidad de horario — {$this->courseNombre}",
            MergeParticipantDecision::RETIRAR => "Preinscripción retirada — {$this->courseNombre}",
        };

        return new Envelope(
            subject: $subject,
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
