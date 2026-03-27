<?php

namespace Tests\Unit\Events;

use App\Events\AgentOffline;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit\Framework\TestCase;

class AgentOfflineTest extends TestCase
{
    private AgentOffline $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new AgentOffline(
            room_id: 'room-123',
            tenant_id: 'tenant-456',
            agent_name: '상담사',
            message: '상담사가 오프라인입니다. 메시지를 남겨주세요.',
        );
    }

    public function test_implements_should_broadcast(): void
    {
        $this->assertInstanceOf(ShouldBroadcast::class, $this->event);
    }

    public function test_broadcasts_on_chat_channel(): void
    {
        $channels = $this->event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-chat.room-123', $channels[0]->name);
    }

    public function test_broadcast_as_returns_event_name(): void
    {
        $this->assertEquals('agent.offline', $this->event->broadcastAs());
    }

    public function test_broadcast_with_returns_payload(): void
    {
        $data = $this->event->broadcastWith();

        $this->assertEquals('room-123', $data['room_id']);
        $this->assertEquals('tenant-456', $data['tenant_id']);
        $this->assertEquals('상담사', $data['agent_name']);
        $this->assertEquals('상담사가 오프라인입니다. 메시지를 남겨주세요.', $data['message']);
    }
}
