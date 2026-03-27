<?php

namespace App\Repositories\Eloquent;

use App\Models\Mongo\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Support\Collection;

class MessageRepository implements MessageRepositoryInterface
{
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function getHistory(string $roomId, int $limit = 50, ?string $before = null): Collection
    {
        $query = Message::where('room_id', $roomId);

        if ($before !== null) {
            $query->where('_id', '<', $before);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(string $roomId, string $excludeSenderType): array
    {
        $unread = Message::where('room_id', $roomId)
            ->where('sender_type', '!=', $excludeSenderType)
            ->where('is_read', false)
            ->get();

        $count = $unread->count();
        $lastId = $count > 0 ? (string) $unread->last()->_id : null;

        if ($count > 0) {
            Message::where('room_id', $roomId)
                ->where('sender_type', '!=', $excludeSenderType)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return ['count' => $count, 'last_id' => $lastId];
    }
}
