<?php

namespace Tests\Unit\Events;

use App\Events\MessageRead;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit\Framework\TestCase;

class MessageReadTest extends TestCase
{
    private MessageRead $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new MessageRead(
            room_id: 'room-123',
            tenant_id: 'tenant-456',
            reader_type: 'agent',
            reader_name: '상담사',
            last_read_message_id: 'msg-789',
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
        $this->assertEquals('message.read', $this->event->broadcastAs());
    }

    public function test_broadcast_with_returns_payload(): void
    {
        $data = $this->event->broadcastWith();

        $this->assertEquals('room-123', $data['room_id']);
        $this->assertEquals('tenant-456', $data['tenant_id']);
        $this->assertEquals('agent', $data['reader_type']);
        $this->assertEquals('상담사', $data['reader_name']);
        $this->assertEquals('msg-789', $data['last_read_message_id']);
    }
}
