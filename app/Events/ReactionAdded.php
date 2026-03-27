<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionAdded implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $room_id,
        public string $message_id,
        public string $emoji,
        public string $user_id,
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
        return 'reaction.added';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room_id,
            'message_id' => $this->message_id,
            'emoji' => $this->emoji,
            'user_id' => $this->user_id,
        ];
    }
}
