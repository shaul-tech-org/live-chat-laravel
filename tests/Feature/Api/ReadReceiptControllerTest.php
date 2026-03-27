<?php

namespace Tests\Feature\Api;

use App\Events\MessageRead;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReadReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test',
            'domain' => 'test.com',
            'api_key' => 'read-test-key',
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_read',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);
    }

    public function test_read_receipt_broadcasts_event(): void
    {
        Event::fake([MessageRead::class]);

        $response = $this->postJson('/api/rooms/' . $this->room->id . '/read', [
            'reader_type' => 'agent',
            'reader_name' => '상담사',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['status', 'updated_count', 'last_read_message_id']]);

        Event::assertDispatched(MessageRead::class, function ($event) {
            return $event->room_id === $this->room->id
                && $event->tenant_id === $this->tenant->id
                && $event->reader_type === 'agent';
        });
    }

    public function test_read_receipt_requires_reader_type(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/read', [
            'reader_name' => '상담사',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_read_receipt_validates_reader_type(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/read', [
            'reader_type' => 'invalid',
            'reader_name' => '상담사',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_read_receipt_room_not_found(): void
    {
        $fakeUuid = '00000000-0000-0000-0000-000000000000';
        $response = $this->postJson('/api/rooms/' . $fakeUuid . '/read', [
            'reader_type' => 'agent',
            'reader_name' => '상담사',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(404);
    }

    public function test_read_receipt_requires_api_key(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/read', [
            'reader_type' => 'agent',
            'reader_name' => '상담사',
        ]);

        $response->assertStatus(401);
    }
}
