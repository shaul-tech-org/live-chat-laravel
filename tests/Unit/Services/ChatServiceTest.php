<?php

namespace Tests\Unit\Services;

use App\Events\MessageSent;
use App\Models\ChatRoom;
use App\Models\FaqEntry;
use App\Models\Mongo\Message;
use App\Models\Tenant;
use App\Services\ChatService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = app(ChatService::class);
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Message::query()->forceDelete();
        parent::tearDown();
    }

    public function test_send_message_creates_mongo_document_and_broadcasts(): void
    {
        Event::fake([MessageSent::class]);

        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_abc12345',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);

        $message = $this->chatService->sendMessage($room, [
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
            'content' => '안녕하세요',
            'content_type' => 'text',
        ]);

        $this->assertNotNull($message);
        $this->assertEquals($room->id, $message->room_id);
        $this->assertEquals($this->tenant->id, $message->tenant_id);
        $this->assertEquals('visitor', $message->sender_type);
        $this->assertEquals('안녕하세요', $message->content);
        $this->assertEquals('text', $message->content_type);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($room) {
            return $event->room_id === $room->id
                && $event->content === '안녕하세요'
                && $event->sender_type === 'visitor';
        });
    }

    public function test_send_message_with_file(): void
    {
        Event::fake([MessageSent::class]);

        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_abc12345',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);

        $message = $this->chatService->sendMessage($room, [
            'sender_type' => 'agent',
            'sender_name' => '상담원',
            'content' => '파일입니다',
            'content_type' => 'file',
            'file_url' => '/uploads/doc.pdf',
        ]);

        $this->assertEquals('/uploads/doc.pdf', $message->file_url);
        $this->assertEquals('file', $message->content_type);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $event->file_url === '/uploads/doc.pdf';
        });
    }

    public function test_send_message_with_reply_to(): void
    {
        Event::fake([MessageSent::class]);

        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_abc12345',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);

        $replyTo = ['id' => 'msg-original', 'content' => '원본 메시지'];

        $message = $this->chatService->sendMessage($room, [
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
            'content' => '답장입니다',
            'content_type' => 'text',
            'reply_to' => $replyTo,
        ]);

        $this->assertEquals($replyTo, $message->reply_to);
    }

    public function test_get_history_returns_messages_ordered_by_created_at(): void
    {
        $roomId = 'room-history-test';

        Message::create([
            'room_id' => $roomId,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
            'content' => '첫 번째',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(2),
        ]);

        Message::create([
            'room_id' => $roomId,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'agent',
            'sender_name' => '상담원',
            'content' => '두 번째',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinute(),
        ]);

        $messages = $this->chatService->getHistory($roomId, 50);

        $this->assertCount(2, $messages);
        // Ordered by created_at desc (newest first)
        $this->assertEquals('두 번째', $messages[0]->content);
        $this->assertEquals('첫 번째', $messages[1]->content);
    }

    public function test_get_history_respects_limit(): void
    {
        $roomId = 'room-limit-test';

        for ($i = 0; $i < 5; $i++) {
            Message::create([
                'room_id' => $roomId,
                'tenant_id' => $this->tenant->id,
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => "메시지 {$i}",
                'content_type' => 'text',
                'is_read' => false,
                'created_at' => now()->subMinutes(5 - $i),
            ]);
        }

        $messages = $this->chatService->getHistory($roomId, 3);
        $this->assertCount(3, $messages);
    }

    public function test_get_history_with_before_cursor(): void
    {
        $roomId = 'room-cursor-test';

        $msg1 = Message::create([
            'room_id' => $roomId,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
            'content' => '오래된 메시지',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        $msg2 = Message::create([
            'room_id' => $roomId,
            'tenant_id' => $this->tenant->id,
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
            'content' => '새로운 메시지',
            'content_type' => 'text',
            'is_read' => false,
            'created_at' => now()->subMinutes(5),
        ]);

        // Get messages before msg2 (should only get msg1)
        $messages = $this->chatService->getHistory($roomId, 50, (string) $msg2->_id);
        $this->assertCount(1, $messages);
        $this->assertEquals('오래된 메시지', $messages[0]->content);
    }

    public function test_check_faq_match_returns_answer_on_keyword_match(): void
    {
        FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '배송',
            'answer' => '배송은 2-3일 소요됩니다.',
            'is_active' => true,
        ]);

        $result = $this->chatService->checkFaqMatch($this->tenant->id, '배송 언제 오나요?');
        $this->assertNotNull($result);
        $this->assertEquals('배송은 2-3일 소요됩니다.', $result);
    }

    public function test_check_faq_match_returns_null_when_no_match(): void
    {
        FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '배송',
            'answer' => '배송은 2-3일 소요됩니다.',
            'is_active' => true,
        ]);

        $result = $this->chatService->checkFaqMatch($this->tenant->id, '반품 하고 싶어요');
        $this->assertNull($result);
    }

    public function test_check_faq_match_ignores_inactive_entries(): void
    {
        FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '배송',
            'answer' => '비활성 답변',
            'is_active' => false,
        ]);

        $result = $this->chatService->checkFaqMatch($this->tenant->id, '배송 언제 오나요?');
        $this->assertNull($result);
    }
}
