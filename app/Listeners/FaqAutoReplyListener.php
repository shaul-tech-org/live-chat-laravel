<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class FaqAutoReplyListener implements ShouldQueue
{
    public function __construct(
        private readonly FaqRepositoryInterface $faqRepo,
        private readonly MessageRepositoryInterface $messageRepo,
        private readonly RoomRepositoryInterface $roomRepo,
    ) {}

    public function handle(MessageSent $event): void
    {
        try {
            $this->process($event);
        } catch (\Exception $e) {
            Log::debug('FaqAutoReplyListener: skipped — ' . $e->getMessage());
        }
    }

    private function process(MessageSent $event): void
    {
        if ($event->sender_type !== 'visitor') {
            return;
        }

        if ($event->content_type !== 'text') {
            return;
        }

        $faq = $this->faqRepo->findMatchingKeyword($event->tenant_id, $event->content);

        if (!$faq) {
            return;
        }

        $room = $this->roomRepo->findById($event->room_id);

        if (!$room) {
            Log::warning('FaqAutoReplyListener: room not found', ['room_id' => $event->room_id]);
            return;
        }

        $message = $this->messageRepo->create([
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
            'sender_type' => 'system',
            'sender_name' => 'FAQ Bot',
            'content' => $faq->answer,
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now(),
        ]);

        broadcast(new MessageSent(
            id: (string) $message->_id,
            room_id: $message->room_id,
            tenant_id: $message->tenant_id,
            sender_type: $message->sender_type,
            sender_name: $message->sender_name,
            content: $message->content,
            content_type: $message->content_type,
            file_url: null,
            reply_to: null,
            created_at: $message->created_at->toISOString(),
        ));

        Log::info('FaqAutoReplyListener: auto-reply sent', [
            'room_id' => $event->room_id,
            'keyword' => $faq->keyword,
        ]);
    }

    public function failed(MessageSent $event, \Throwable $exception): void
    {
        Log::debug('FaqAutoReplyListener: failed — ' . $exception->getMessage());
    }
}
