<?php

namespace Tests\Feature\Broadcasting;

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use App\Services\BuiltinAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;
    private Agent $agent;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure built-in auth and broadcasting for testing
        config([
            'chat.admin_email' => 'admin@test.com',
            'chat.admin_password' => 'test-password',
            'broadcasting.connections.reverb.key' => 'test-reverb-key',
            'broadcasting.connections.reverb.secret' => 'test-reverb-secret',
            'broadcasting.connections.reverb.app_id' => 'test-reverb-app',
        ]);
        $this->app->singleton(BuiltinAuthService::class, function () {
            return new BuiltinAuthService('admin@test.com', 'test-password');
        });

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'api_key' => 'test-api-key-123',
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_12345678',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);

        $this->agent = Agent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => 'user-1',
            'name' => '상담사',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_online' => true,
            'is_active' => true,
        ]);

        // Login to get token
        $authService = app(BuiltinAuthService::class);
        $this->adminToken = $authService->login('admin@test.com', 'test-password');
    }

    // --- Chat Channel (visitor with API key) ---

    public function test_visitor_can_auth_chat_channel_with_api_key(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $this->room->id,
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_visitor_cannot_auth_other_tenant_room(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other',
            'domain' => 'other.com',
            'api_key' => 'other-key-456',
            'owner_id' => 'other-owner',
            'is_active' => true,
        ]);

        $otherRoom = ChatRoom::create([
            'tenant_id' => $otherTenant->id,
            'visitor_id' => 'v_other',
            'visitor_name' => '다른 방문자',
            'status' => 'open',
        ]);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $otherRoom->id,
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(403);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $this->room->id,
        ], [
            'X-API-Key' => 'invalid-key',
        ]);

        $response->assertStatus(401);
    }

    // --- Chat Channel (agent with bearer token) ---

    public function test_agent_can_auth_chat_channel_with_bearer(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $this->room->id,
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_agent_with_invalid_token_returns_401(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $this->room->id,
        ], [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
    }

    // --- Admin Channel ---

    public function test_admin_can_auth_admin_channel(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-admin.' . $this->tenant->id,
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_admin_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-admin.' . $this->tenant->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_for_nonexistent_tenant_returns_404(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-admin.00000000-0000-0000-0000-000000000000',
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(404);
    }

    public function test_agent_not_in_tenant_returns_403(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other',
            'domain' => 'other.com',
            'api_key' => 'other-key-789',
            'owner_id' => 'other-owner',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-admin.' . $otherTenant->id,
        ], [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ]);

        $response->assertStatus(403);
    }

    // --- Validation ---

    public function test_missing_socket_id_returns_422(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-chat.' . $this->room->id,
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_unknown_channel_returns_403(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-unknown.something',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(403);
    }

    public function test_no_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123456.654321',
            'channel_name' => 'private-chat.' . $this->room->id,
        ]);

        $response->assertStatus(401);
    }
}
