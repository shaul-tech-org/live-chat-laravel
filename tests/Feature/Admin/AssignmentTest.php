<?php

namespace Tests\Feature\Admin;

use App\Enums\AssignmentMethod;
use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);
    }

    // ── assign ──

    public function test_manual_assign_room(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $agent = $this->createAgent();
        $room = $this->createRoom();

        $response = $this->postJson("/api/admin/rooms/{$room->id}/assign", [
            'agent_id' => $agent->id,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_agent_id', $agent->id)
            ->assertJsonPath('data.assignment_method', 'manual');

        $this->assertDatabaseHas('chat_rooms', [
            'id' => $room->id,
            'assigned_agent_id' => $agent->id,
            'assignment_method' => 'manual',
        ]);
    }

    public function test_assign_requires_agent_id(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $room = $this->createRoom();

        $response = $this->postJson("/api/admin/rooms/{$room->id}/assign", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(422);
    }

    public function test_assign_returns_404_for_nonexistent_room(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $agent = $this->createAgent();
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->postJson("/api/admin/rooms/{$fakeId}/assign", [
            'agent_id' => $agent->id,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(404);
    }

    // ── transfer ──

    public function test_transfer_room(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $agentA = $this->createAgent(['name' => '상담원A']);
        $agentB = $this->createAgent(['name' => '상담원B']);
        $room = $this->createRoom(['assigned_agent_id' => $agentA->id]);

        $response = $this->postJson("/api/admin/rooms/{$room->id}/transfer", [
            'agent_id' => $agentB->id,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_agent_id', $agentB->id);
    }

    public function test_transfer_to_same_agent_returns_403(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $agent = $this->createAgent();
        $room = $this->createRoom(['assigned_agent_id' => $agent->id]);

        $response = $this->postJson("/api/admin/rooms/{$room->id}/transfer", [
            'agent_id' => $agent->id,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(403);
    }

    // ── unassign ──

    public function test_unassign_room(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $agent = $this->createAgent();
        $room = $this->createRoom([
            'assigned_agent_id' => $agent->id,
            'assignment_method' => 'manual',
        ]);

        $response = $this->postJson("/api/admin/rooms/{$room->id}/unassign", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_agent_id', null)
            ->assertJsonPath('data.assignment_method', null);

        $this->assertDatabaseHas('chat_rooms', [
            'id' => $room->id,
            'assigned_agent_id' => null,
            'assignment_method' => null,
        ]);
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
            'user_id' => 'assign-user-' . $counter . '-' . bin2hex(random_bytes(4)),
            'name' => '상담원' . $counter,
            'email' => "assign-agent{$counter}@example.com",
            'role' => 'agent',
            'is_online' => false,
            'is_active' => true,
        ], $overrides));
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
