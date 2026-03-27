<?php

namespace Tests\Unit\Models;

use App\Enums\RoomStatus;
use App\Models\ChatRoom;
use PHPUnit\Framework\TestCase;

class ChatRoomTest extends TestCase
{
    public function test_chat_room_fillable_attributes(): void
    {
        $room = new ChatRoom();
        $this->assertContains('tenant_id', $room->getFillable());
        $this->assertContains('visitor_id', $room->getFillable());
        $this->assertContains('status', $room->getFillable());
    }

    public function test_chat_room_casts_status_to_enum(): void
    {
        $room = new ChatRoom();
        $casts = $room->getCasts();
        $this->assertEquals(RoomStatus::class, $casts['status']);
    }

    public function test_room_status_enum_values(): void
    {
        $this->assertEquals('open', RoomStatus::Open->value);
        $this->assertEquals('closed', RoomStatus::Closed->value);
    }

    public function test_chat_room_uses_uuid_primary_key(): void
    {
        $room = new ChatRoom();
        $this->assertEquals('string', $room->getKeyType());
        $this->assertFalse($room->getIncrementing());
    }
}
