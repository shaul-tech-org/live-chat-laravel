<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OfflineMessageNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $roomId,
        public readonly string $senderName,
        public readonly string $messageContent,
        public readonly ?string $visitorEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Live Chat] 오프라인 메시지 — {$this->senderName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.offline-message',
            with: [
                'roomId' => $this->roomId,
                'senderName' => $this->senderName,
                'messageContent' => $this->messageContent,
                'visitorEmail' => $this->visitorEmail,
            ],
        );
    }
}
