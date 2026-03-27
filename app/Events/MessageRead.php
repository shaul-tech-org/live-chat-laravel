<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $room_id,
        public string $tenant_id,
        public string $reader_type,
        public string $reader_name,
        public ?string $last_read_message_id = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'reader_type' => $this->reader_type,
            'reader_name' => $this->reader_name,
            'last_read_message_id' => $this->last_read_message_id,
        ];
    }
}
