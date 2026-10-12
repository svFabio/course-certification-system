<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseSlotAvailableNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Course $course,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Cupo disponible — {$this->course->nombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-slot-available',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
