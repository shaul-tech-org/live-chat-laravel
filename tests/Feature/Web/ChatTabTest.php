<?php

namespace Tests\Feature\Web;

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ChatTabTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $adminToken = '';
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'chat.admin_email' => 'admin@test.com',
            'chat.admin_password' => 'test-password',
        ]);
        $this->app->singleton(\App\Services\BuiltinAuthService::class, function () {
            return new \App\Services\BuiltinAuthService('admin@test.com', 'test-password');
        });
        $authService = app(\App\Services\BuiltinAuthService::class);
        $this->adminToken = $authService->login('admin@test.com', 'test-password');

        $this->tenant = Tenant::create([
            'name' => 'Test', 'domain' => 'test.com',
            'api_key' => 'test-key', 'owner_id' => 'owner', 'is_active' => true,
        ]);
    }

    public function test_chat_tab_shows_room_list_area(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('chatTab()', false);
        $response->assertSee('selectedRoom', false);
    }

    public function test_chat_tab_has_search_and_filter(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('search', false);
        $response->assertSee('filter', false);
    }

    public function test_chat_tab_has_message_input(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('newMessage', false);
        $response->assertSee('sendMessage', false);
    }

    public function test_admin_rooms_api_returns_rooms(): void
    {
        ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_test1',
            'visitor_name' => '방문자1',
            'status' => 'open',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/rooms');

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_admin_can_send_message_to_room(): void
    {
        $this->skipIfNoMongo();
        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_test2',
            'visitor_name' => '방문자2',
            'status' => 'open',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => $this->tenant->api_key,
        ])->postJson('/api/rooms/' . $room->id . '/messages', [
            'sender_type' => 'agent',
            'sender_name' => '상담사',
            'content' => '안녕하세요',
            'content_type' => 'text',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }
}
