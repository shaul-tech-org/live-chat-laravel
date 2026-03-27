<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $tenant_id,
        public string $agent_id,
        public string $agent_name,
        public bool $is_online,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.' . $this->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->agent_name,
            'is_online' => $this->is_online,
        ];
    }
}
