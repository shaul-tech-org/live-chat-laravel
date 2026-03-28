<?php

namespace Tests\Feature\Admin;

use App\Models\Agent;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
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
        ]);
    }

    public function test_create_agent(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/agents', [
            'tenant_id' => $this->tenant->id,
            'user_id' => 'user-001',
            'name' => '상담원A',
            'email' => 'agent@example.com',
            'role' => 'agent',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'tenant_id', 'user_id', 'name', 'email', 'role']]);
        $this->assertEquals('상담원A', $response->json('data.name'));
        $this->assertDatabaseHas('agents', ['user_id' => 'user-001']);
    }

    public function test_create_agent_validation_error(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/agents', [
            // missing required fields
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(422);
    }

    public function test_list_agents(): void
    {
        Agent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => 'user-list-001',
            'name' => '상담원B',
            'email' => 'agentb@example.com',
            'role' => 'agent',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/agents', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_delete_agent(): void
    {
        $agent = Agent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => 'user-del-001',
            'name' => '삭제대상',
            'email' => 'del@example.com',
            'role' => 'agent',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->deleteJson("/api/admin/agents/{$agent->id}", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
    }

    public function test_delete_agent_not_found(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->deleteJson("/api/admin/agents/{$fakeId}", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404);
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
