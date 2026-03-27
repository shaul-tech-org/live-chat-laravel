<?php

namespace App\Mail;

use App\Models\ChatRoom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TranscriptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ChatRoom $room,
        public readonly Collection $messages,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Live Chat] 대화 내역 — {$this->room->visitor_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transcript',
            with: [
                'room' => $this->room,
                'messages' => $this->messages,
            ],
        );
    }
}
