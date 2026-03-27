<?php

namespace Tests\Unit\Events;

use App\Events\MessageSent;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class MessageSentTest extends TestCase
{
    private MessageSent $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = new MessageSent(
            id: 'msg-001',
            room_id: 'room-abc',
            tenant_id: 'tenant-xyz',
            sender_type: 'visitor',
            sender_name: '방문자',
            content: '안녕하세요',
            content_type: 'text',
            file_url: null,
            reply_to: null,
            created_at: '2026-03-26T12:00:00Z',
        );
    }

    public function test_broadcast_on_returns_correct_channels(): void
    {
        $channels = $this->event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);

        // Check channel names (PrivateChannel prepends "private-")
        $this->assertEquals('private-chat.room-abc', $channels[0]->name);
        $this->assertEquals('private-admin.tenant-xyz', $channels[1]->name);
    }

    public function test_broadcast_as_returns_event_name(): void
    {
        $this->assertEquals('message.sent', $this->event->broadcastAs());
    }

    public function test_broadcast_with_returns_correct_payload(): void
    {
        $data = $this->event->broadcastWith();

        $this->assertEquals('msg-001', $data['id']);
        $this->assertEquals('room-abc', $data['room_id']);
        $this->assertEquals('tenant-xyz', $data['tenant_id']);
        $this->assertEquals('visitor', $data['sender_type']);
        $this->assertEquals('방문자', $data['sender_name']);
        $this->assertEquals('안녕하세요', $data['content']);
        $this->assertEquals('text', $data['content_type']);
        $this->assertNull($data['file_url']);
        $this->assertNull($data['reply_to']);
        $this->assertEquals('2026-03-26T12:00:00Z', $data['created_at']);
    }

    public function test_broadcast_with_includes_file_url_when_present(): void
    {
        $event = new MessageSent(
            id: 'msg-002',
            room_id: 'room-abc',
            tenant_id: 'tenant-xyz',
            sender_type: 'agent',
            sender_name: '상담원',
            content: '파일 전송',
            content_type: 'file',
            file_url: '/uploads/doc.pdf',
            reply_to: ['id' => 'msg-001', 'content' => '안녕하세요'],
            created_at: '2026-03-26T12:01:00Z',
        );

        $data = $event->broadcastWith();
        $this->assertEquals('/uploads/doc.pdf', $data['file_url']);
        $this->assertEquals(['id' => 'msg-001', 'content' => '안녕하세요'], $data['reply_to']);
    }

    public function test_implements_should_broadcast(): void
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $this->event);
    }
}
