<?php

namespace Tests\Feature\Agent;

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AgentRoomTest extends TestCase
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
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);
    }

    // ── GET /api/agent/rooms ──

    public function test_rooms_index_returns_categorized_rooms(): void
    {
        // 내 대화 (admin-builtin에 배정됨)
        $myRoom = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        // 대기 중 (미배정)
        $waitingRoom = $this->createRoom();

        // 종료된 방
        $closedRoom = $this->createRoom([
            'assigned_agent_id' => 'admin-builtin',
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $response = $this->getJson('/api/agent/rooms', [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'my_rooms',
                    'waiting_rooms',
                    'closed_rooms',
                ],
            ]);

        $data = $response->json('data');

        // 내 대화에 배정된 방이 있어야 함
        $myRoomIds = collect($data['my_rooms'])->pluck('id')->toArray();
        $this->assertContains($myRoom->id, $myRoomIds);

        // 대기 중에 미배정 방이 있어야 함
        $waitingIds = collect($data['waiting_rooms'])->pluck('id')->toArray();
        $this->assertContains($waitingRoom->id, $waitingIds);

        // 종료에 closed 방이 있어야 함
        $closedIds = collect($data['closed_rooms'])->pluck('id')->toArray();
        $this->assertContains($closedRoom->id, $closedIds);
    }

    public function test_rooms_index_requires_auth(): void
    {
        $response = $this->getJson('/api/agent/rooms');

        $response->assertStatus(401);
    }

    // ── POST /api/agent/rooms/{id}/assign ──

    public function test_assign_room_to_self(): void
    {
        $room = $this->createRoom();

        $response = $this->postJson("/api/agent/rooms/{$room->id}/assign", [], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_agent_id', 'admin-builtin');
    }

    public function test_assign_already_assigned_room_returns_409(): void
    {
        $room = $this->createRoom(['assigned_agent_id' => 'other-agent']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/assign", [], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(409);
    }

    // ── POST /api/agent/rooms/{id}/close ──

    public function test_close_room(): void
    {
        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/close", [], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('chat_rooms', [
            'id' => $room->id,
            'status' => 'closed',
        ]);
    }

    // ── POST /api/agent/rooms/{id}/transfer ──

    public function test_transfer_room_to_another_agent(): void
    {
        $agent = $this->createAgent();
        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/transfer", [
            'agent_id' => $agent->id,
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_agent_id', $agent->id);
    }

    public function test_transfer_requires_agent_id(): void
    {
        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/transfer", [], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(422);
    }

    // ── GET /api/agent/rooms/{id}/messages ──

    public function test_get_room_messages(): void
    {
        try {
            \App\Models\Mongo\Message::query()->count();
        } catch (\Exception $e) {
            $this->markTestSkipped('MongoDB 연결/인증 불가 — 테스트 환경에서 생략');
        }

        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->getJson("/api/agent/rooms/{$room->id}/messages", [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ── POST /api/agent/rooms/{id}/messages ──

    public function test_send_message_to_room(): void
    {
        try {
            \App\Models\Mongo\Message::query()->count();
        } catch (\Exception $e) {
            $this->markTestSkipped('MongoDB 연결/인증 불가 — 테스트 환경에서 생략');
        }

        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/messages", [
            'content' => '테스트 메시지입니다.',
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', '테스트 메시지입니다.');
    }

    public function test_send_message_requires_content(): void
    {
        $room = $this->createRoom(['assigned_agent_id' => 'admin-builtin']);

        $response = $this->postJson("/api/agent/rooms/{$room->id}/messages", [], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(422);
    }

    // ── GET /api/agent/agents ──

    public function test_list_agents_for_transfer(): void
    {
        $this->createAgent(['name' => '상담원1']);
        $this->createAgent(['name' => '상담원2']);

        $response = $this->getJson('/api/agent/agents', [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ── helpers ──

    private function createRoom(array $overrides = []): ChatRoom
    {
        return ChatRoom::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_' . substr(bin2hex(random_bytes(4)), 0, 8),
            'visitor_name' => '방문자',
            'status' => 'open',
        ], $overrides));
    }

    private function createAgent(array $overrides = []): Agent
    {
        static $counter = 0;
        $counter++;

        return Agent::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'user_id' => 'agent-user-' . $counter . '-' . bin2hex(random_bytes(4)),
            'name' => '상담원' . $counter,
            'email' => "agent{$counter}@example.com",
            'role' => 'agent',
            'is_online' => false,
            'is_active' => true,
        ], $overrides));
    }
}
