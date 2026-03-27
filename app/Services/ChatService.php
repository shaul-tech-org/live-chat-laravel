<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\ChatRoom;
use App\Models\Mongo\Message;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Support\Collection;

class ChatService
{
    public function __construct(
        private readonly MessageRepositoryInterface $messageRepo,
        private readonly FaqRepositoryInterface $faqRepo,
    ) {}

    public function sendMessage(ChatRoom $room, array $data): Message
    {
        $message = $this->messageRepo->create([
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
            'sender_type' => $data['sender_type'],
            'sender_name' => $data['sender_name'],
            'content' => $data['content'],
            'content_type' => $data['content_type'],
            'file_url' => $data['file_url'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
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
            file_url: $message->file_url,
            reply_to: $message->reply_to,
            created_at: $message->created_at->toISOString(),
        ));

        if ($data['sender_type'] === 'visitor' && $data['content_type'] === 'text') {
            $faqAnswer = $this->checkFaqMatch($room->tenant_id, $data['content']);
            if ($faqAnswer) {
                $this->sendFaqReply($room, $faqAnswer);
            }
        }

        return $message;
    }

    public function getHistory(string $roomId, int $limit = 50, ?string $before = null): Collection
    {
        return $this->messageRepo->getHistory($roomId, $limit, $before);
    }

    public function checkFaqMatch(string $tenantId, string $content): ?string
    {
        $faq = $this->faqRepo->findMatchingKeyword($tenantId, $content);

        return $faq?->answer;
    }

    public function sendFaqReply(ChatRoom $room, string $answer): void
    {
        $message = $this->messageRepo->create([
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
            'sender_type' => 'system',
            'sender_name' => 'FAQ Bot',
            'content' => $answer,
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
    }
}
