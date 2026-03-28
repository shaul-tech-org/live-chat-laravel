<?php

namespace Tests\Feature\Admin;

use App\Events\TypingStarted;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RoomTypingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    private ChatRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'visitor-001',
            'visitor_name' => '테스트 방문자',
            'status' => 'open',
        ]);
    }

    public function test_admin_typing_broadcasts_event(): void
    {
        Event::fake([TypingStarted::class]);

        $token = $this->adminLogin();
        if (! $token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson("/api/admin/rooms/{$this->room->id}/typing", [
            'sender_name' => '상담사',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status' => 'ok']]);

        Event::assertDispatched(TypingStarted::class, function (TypingStarted $event) {
            return $event->room_id === $this->room->id
                && $event->sender_type === 'agent'
                && $event->sender_name === '상담사';
        });
    }

    public function test_admin_typing_defaults_sender_name(): void
    {
        Event::fake([TypingStarted::class]);

        $token = $this->adminLogin();
        if (! $token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson("/api/admin/rooms/{$this->room->id}/typing", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(TypingStarted::class, function (TypingStarted $event) {
            return $event->sender_name === '상담사';
        });
    }

    public function test_admin_typing_room_not_found(): void
    {
        $token = $this->adminLogin();
        if (! $token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/rooms/nonexistent-id/typing', [
            'sender_name' => '상담사',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(404);
    }

    public function test_admin_typing_requires_auth(): void
    {
        $response = $this->postJson("/api/admin/rooms/{$this->room->id}/typing", [
            'sender_name' => '상담사',
        ]);

        $response->assertStatus(401);
    }

    private function adminLogin(): ?string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => config('chat.admin_email'),
            'password' => config('chat.admin_password'),
        ]);

        if ($response->status() !== 200) {
            return null;
        }

        return $response->json('data.accessToken');
    }
}
