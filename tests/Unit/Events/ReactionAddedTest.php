<?php

namespace Tests\Unit\Events;

use App\Events\ReactionAdded;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class ReactionAddedTest extends TestCase
{
    public function test_broadcast_on_returns_chat_channel(): void
    {
        $event = new ReactionAdded(
            room_id: 'room-abc',
            message_id: 'msg-001',
            emoji: '👍',
            user_id: 'user-123',
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-chat.room-abc', $channels[0]->name);
    }

    public function test_broadcast_as_returns_event_name(): void
    {
        $event = new ReactionAdded(
            room_id: 'room-abc',
            message_id: 'msg-001',
            emoji: '👍',
            user_id: 'user-123',
        );

        $this->assertEquals('reaction.added', $event->broadcastAs());
    }

    public function test_broadcast_with_returns_correct_payload(): void
    {
        $event = new ReactionAdded(
            room_id: 'room-abc',
            message_id: 'msg-001',
            emoji: '👍',
            user_id: 'user-123',
        );

        $data = $event->broadcastWith();
        $this->assertEquals('room-abc', $data['room_id']);
        $this->assertEquals('msg-001', $data['message_id']);
        $this->assertEquals('👍', $data['emoji']);
        $this->assertEquals('user-123', $data['user_id']);
    }

    public function test_implements_should_broadcast(): void
    {
        $event = new ReactionAdded(
            room_id: 'room-abc',
            message_id: 'msg-001',
            emoji: '👍',
            user_id: 'user-123',
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }
}
