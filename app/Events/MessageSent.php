<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $id,
        public string $room_id,
        public string $tenant_id,
        public string $sender_type,
        public string $sender_name,
        public string $content,
        public string $content_type,
        public ?string $file_url = null,
        public ?array $reply_to = null,
        public string $created_at = '',
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->room_id),
            new PrivateChannel('admin.' . $this->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->sender_name,
            'content' => $this->content,
            'content_type' => $this->content_type,
            'file_url' => $this->file_url,
            'reply_to' => $this->reply_to,
            'created_at' => $this->created_at,
        ];
    }
}
