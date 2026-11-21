<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LetterDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $employeeName,
        public string $letterType,
        public string $status,
        public string $details,
        public ?string $url = null,
        public ?string $adminNotes = null
    ) {}

    public function envelope(): Envelope
    {
        $verb = $this->status === 'approved' ? 'Approved' : 'Rejected';

        return new Envelope(
            subject: "Your {$this->letterType} was {$verb} — INHS SMART-DTR"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.letter-decision');
    }
}
