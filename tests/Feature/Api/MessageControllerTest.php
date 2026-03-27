<?php

namespace Tests\Feature\Api;

use App\Events\MessageSent;
use App\Models\ChatRoom;
use App\Models\Mongo\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_test1234',
            'visitor_name' => '테스트 방문자',
            'status' => 'open',
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up MongoDB test data
        Message::where('room_id', $this->room->id)->forceDelete();
        parent::tearDown();
    }

    public function test_send_message_success(): void
    {
        Event::fake([MessageSent::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '테스트 방문자',
                'content' => '안녕하세요!',
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => [
                'id',
                'room_id',
                'tenant_id',
                'sender_type',
                'sender_name',
                'content',
                'content_type',
                'created_at',
            ]]);

        $this->assertEquals('안녕하세요!', $response->json('data.content'));
        $this->assertEquals($this->room->id, $response->json('data.room_id'));

        Event::assertDispatched(MessageSent::class);
    }

    public function test_send_message_validation_error(): void
    {
        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                // missing required fields
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(422);
    }

    public function test_send_message_room_not_found(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->postJson(
            "/api/rooms/{$fakeId}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => '테스트',
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(404);
    }

    public function test_send_message_requires_api_key(): void
    {
        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => '테스트',
                'content_type' => 'text',
            ],
        );

        $response->assertStatus(401);
    }

    public function test_get_message_history(): void
    {
        // Insert test messages into MongoDB
        Message::create([
            'room_id' => $this->room->id,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'visitor',
            'sender_name' => '테스트 방문자',
            'content' => '첫 번째 메시지',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(2),
        ]);

        Message::create([
            'room_id' => $this->room->id,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'agent',
            'sender_name' => '상담원',
            'content' => '두 번째 메시지',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->getJson(
            "/api/rooms/{$this->room->id}/messages",
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => [
                'data',
                'meta' => ['room_id', 'count'],
            ]]);

        $this->assertEquals(2, $response->json('data.meta.count'));
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
        $firstMessage = $data[0];
        $this->assertArrayHasKey('room_id', $firstMessage);
        $this->assertArrayHasKey('content', $firstMessage);
        $this->assertArrayHasKey('sender_type', $firstMessage);
    }

    public function test_get_message_history_with_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Message::create([
                'room_id' => $this->room->id,
                'tenant_id' => $this->tenant->id,
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => "메시지 {$i}",
                'content_type' => 'text',
                'is_read' => false,
                'created_at' => now()->subMinutes(5 - $i),
            ]);
        }

        $response = $this->getJson(
            "/api/rooms/{$this->room->id}/messages?limit=3",
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_get_message_history_room_not_found(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->getJson(
            "/api/rooms/{$fakeId}/messages",
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(404);
    }

    public function test_send_message_with_file_url(): void
    {
        Event::fake([MessageSent::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => '이미지입니다',
                'content_type' => 'image',
                'file_url' => '/uploads/img.png',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $this->assertEquals('/uploads/img.png', $response->json('data.file_url'));
    }

    public function test_send_message_triggers_faq_auto_reply(): void
    {
        Event::fake([MessageSent::class]);

        // Create FAQ entry
        \App\Models\FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '배송',
            'answer' => '배송은 2-3일 소요됩니다.',
            'is_active' => true,
        ]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => '배송 언제 오나요?',
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);

        // MessageSent dispatched at least twice: once for visitor, once for FAQ auto-reply
        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $event->sender_type === 'visitor' && $event->content === '배송 언제 오나요?';
        });
        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $event->sender_type === 'system' && str_contains($event->content, '배송은 2-3일');
        });
    }
}
