<?php

namespace Tests\Unit\Events;

use App\Events\SystemMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit\Framework\TestCase;

class SystemMessageTest extends TestCase
{
    private SystemMessage $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new SystemMessage(
            room_id: 'room-123',
            tenant_id: 'tenant-456',
            content: '운영 시간이 종료되었습니다.',
            type: 'info',
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
        $this->assertEquals('system.message', $this->event->broadcastAs());
    }

    public function test_broadcast_with_returns_payload(): void
    {
        $data = $this->event->broadcastWith();

        $this->assertEquals('room-123', $data['room_id']);
        $this->assertEquals('tenant-456', $data['tenant_id']);
        $this->assertEquals('운영 시간이 종료되었습니다.', $data['content']);
        $this->assertEquals('info', $data['type']);
    }

    public function test_default_type_is_info(): void
    {
        $event = new SystemMessage(
            room_id: 'room-1',
            tenant_id: 'tenant-1',
            content: 'test',
        );

        $this->assertEquals('info', $event->broadcastWith()['type']);
    }
}
