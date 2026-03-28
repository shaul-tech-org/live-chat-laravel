<?php

namespace Tests\Feature\Security;

use App\Models\ChatRoom;
use App\Models\Feedback;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class XssTest extends TestCase
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
            'name' => 'XSS Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_xss_test',
            'visitor_name' => '테스트 방문자',
            'status' => 'open',
        ]);
    }

    public function test_script_tag_in_message_is_escaped(): void
    {
        $this->skipIfNoMongo();
        $xssPayload = '<script>alert(1)</script>';

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => $xssPayload,
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $storedContent = $response->json('data.content');
        $this->assertStringNotContainsString('<script>', $storedContent);
        $this->assertStringNotContainsString('</script>', $storedContent);
    }

    public function test_img_onerror_in_message_is_escaped(): void
    {
        $this->skipIfNoMongo();
        $xssPayload = '<img onerror=alert(1)>';

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => $xssPayload,
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $storedContent = $response->json('data.content');
        $this->assertStringNotContainsString('<img', $storedContent);
        $this->assertStringContainsString('&lt;img', $storedContent);
    }

    public function test_xss_in_visitor_name(): void
    {
        $xssPayload = '<script>alert(1)</script>';

        $response = $this->postJson(
            '/api/rooms',
            ['visitor_name' => $xssPayload],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $visitorName = $response->json('data.visitor_name');
        if ($visitorName !== null) {
            $this->assertStringNotContainsString('<script>', $visitorName);
            $this->assertStringNotContainsString('</script>', $visitorName);
        }
    }

    public function test_xss_in_feedback_comment(): void
    {
        $xssPayload = '<script>document.cookie</script>';

        $response = $this->postJson(
            '/api/feedbacks',
            [
                'room_id' => $this->room->id,
                'rating' => 4,
                'comment' => $xssPayload,
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $storedComment = $response->json('data.comment');
        if ($storedComment !== null) {
            $this->assertStringNotContainsString('<script>', $storedComment);
            $this->assertStringNotContainsString('</script>', $storedComment);
        }
    }
}
