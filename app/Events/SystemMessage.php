<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemMessage implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $room_id,
        public string $tenant_id,
        public string $content,
        public string $type = 'info',
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
        return 'system.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'content' => $this->content,
            'type' => $this->type,
        ];
    }
}
