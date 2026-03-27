<?php

namespace Tests\Unit\Events;

use App\Events\TypingStarted;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class TypingStartedTest extends TestCase
{
    public function test_broadcast_on_returns_chat_channel(): void
    {
        $event = new TypingStarted(
            room_id: 'room-abc',
            sender_type: 'visitor',
            sender_name: '방문자',
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-chat.room-abc', $channels[0]->name);
    }

    public function test_broadcast_as_returns_event_name(): void
    {
        $event = new TypingStarted(
            room_id: 'room-abc',
            sender_type: 'agent',
            sender_name: '상담원',
        );

        $this->assertEquals('typing.started', $event->broadcastAs());
    }

    public function test_broadcast_with_returns_correct_payload(): void
    {
        $event = new TypingStarted(
            room_id: 'room-abc',
            sender_type: 'visitor',
            sender_name: '방문자',
        );

        $data = $event->broadcastWith();
        $this->assertEquals('room-abc', $data['room_id']);
        $this->assertEquals('visitor', $data['sender_type']);
        $this->assertEquals('방문자', $data['sender_name']);
    }

    public function test_implements_should_broadcast(): void
    {
        $event = new TypingStarted(
            room_id: 'room-abc',
            sender_type: 'visitor',
            sender_name: '방문자',
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }
}
