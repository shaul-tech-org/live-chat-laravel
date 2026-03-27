<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentOffline implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $room_id,
        public string $tenant_id,
        public string $agent_name,
        public string $message,
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
        return 'agent.offline';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'agent_name' => $this->agent_name,
            'message' => $this->message,
        ];
    }
}
