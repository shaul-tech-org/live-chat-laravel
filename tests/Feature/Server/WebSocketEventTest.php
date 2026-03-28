<?php

namespace Tests\Feature\Server;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\ReactionAdded;
use App\Events\TypingStarted;
use App\Models\ChatRoom;
use App\Models\Mongo\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebSocketEventTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'WebSocket Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_ws_test',
            'visitor_name' => '웹소켓 테스트 방문자',
            'status' => 'open',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            Message::where('room_id', $this->room->id)->forceDelete();
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            // MongoDB 미연결 환경 — 무시
        }
        parent::tearDown();
    }

    public function test_message_creates_and_dispatches_event(): void
    {
        $this->skipIfNoMongo();
        Event::fake([MessageSent::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => '메시지 전송 테스트',
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $response->assertJsonPath('data.content', '메시지 전송 테스트');

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $event->room_id === $this->room->id
                && $event->content === '메시지 전송 테스트'
                && $event->sender_type === 'visitor';
        });
    }

    public function test_typing_dispatches_event(): void
    {
        Event::fake([TypingStarted::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/typing",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertOk();

        Event::assertDispatched(TypingStarted::class, function (TypingStarted $event) {
            return $event->room_id === $this->room->id
                && $event->sender_type === 'visitor'
                && $event->sender_name === '방문자';
        });
    }

    public function test_reaction_dispatches_event(): void
    {
        $this->skipIfNoMongo();
        Event::fake([ReactionAdded::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/reactions",
            [
                'message_id' => 'msg-ws-test-001',
                'emoji' => '👍',
                'user_id' => 'v_ws_test',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);

        Event::assertDispatched(ReactionAdded::class, function (ReactionAdded $event) {
            return $event->room_id === $this->room->id
                && $event->message_id === 'msg-ws-test-001'
                && $event->emoji === '👍';
        });
    }

    public function test_read_receipt_dispatches_event(): void
    {
        $this->skipIfNoMongo();
        Event::fake([MessageRead::class]);

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/read",
            [
                'reader_type' => 'agent',
                'reader_name' => '상담원',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertOk();

        Event::assertDispatched(MessageRead::class, function (MessageRead $event) {
            return $event->room_id === $this->room->id
                && $event->tenant_id === $this->tenant->id
                && $event->reader_type === 'agent'
                && $event->reader_name === '상담원';
        });
    }
}
