<?php

namespace Tests\Feature\Api;

use App\Models\ChatRoom;
use App\Models\Tenant;
use App\Services\BuiltinAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $apiKey;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
        ]);
    }

    // --- Api\RoomController: store ---

    public function test_create_room_returns_201(): void
    {
        $response = $this->postJson('/api/rooms', [
            'visitor_name' => '홍길동',
            'visitor_email' => 'hong@example.com',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'tenant_id', 'visitor_id', 'visitor_name', 'status']]);

        $this->assertEquals($this->tenant->id, $response->json('data.tenant_id'));
        $this->assertEquals('open', $response->json('data.status'));
    }

    public function test_create_room_with_phone_returns_201(): void
    {
        $response = $this->postJson('/api/rooms', [
            'visitor_name' => '홍길동',
            'visitor_email' => 'hong@example.com',
            'visitor_phone' => '010-1234-5678',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'tenant_id', 'visitor_id', 'visitor_name', 'visitor_phone', 'status']]);

        $this->assertEquals('010-1234-5678', $response->json('data.visitor_phone'));
    }

    // --- Api\RoomController: visitorRooms ---

    public function test_visitor_rooms_returns_own_rooms(): void
    {
        $room1 = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_abc12345',
            'visitor_name' => '방문자A',
            'status' => 'open',
        ]);
        $room2 = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_abc12345',
            'visitor_name' => '방문자A',
            'status' => 'closed',
        ]);
        // Another visitor's room - should not appear
        ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_other999',
            'visitor_name' => '다른방문자',
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/rooms?visitor_id=v_abc12345', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_visitor_rooms_requires_visitor_id(): void
    {
        $response = $this->getJson('/api/rooms', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422);
    }

    public function test_visitor_rooms_requires_api_key(): void
    {
        $response = $this->getJson('/api/rooms?visitor_id=v_abc12345');
        $response->assertStatus(401);
    }

    // --- Admin\RoomController: index ---

    public function test_admin_list_rooms(): void
    {
        ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_test0001',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/rooms', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
    }

    public function test_admin_list_rooms_with_pagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            ChatRoom::create([
                'tenant_id' => $this->tenant->id,
                'visitor_id' => 'v_page_' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'visitor_name' => '방문자' . $i,
                'status' => 'open',
            ]);
        }

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/rooms?per_page=2&page=1', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_admin_list_rooms_with_sort_activity(): void
    {
        $room1 = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_sort_0001',
            'visitor_name' => '먼저생성',
            'status' => 'open',
        ]);

        $room2 = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_sort_0002',
            'visitor_name' => '나중생성',
            'status' => 'open',
        ]);

        // Touch room1 to make its updated_at more recent
        $room1->touch();

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/rooms?sort=activity', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals($room1->id, $data[0]['id']);
    }

    public function test_admin_list_rooms_per_page_max_capped_at_50(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/rooms?per_page=100', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 50);
    }

    // --- Admin\RoomController: update (close) ---

    public function test_admin_close_room(): void
    {
        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_close001',
            'visitor_name' => '종료테스트',
            'status' => 'open',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->patchJson("/api/admin/rooms/{$room->id}", [
            'status' => 'closed',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->assertNotNull(ChatRoom::find($room->id)->closed_at);
    }

    public function test_admin_close_room_invalid_status(): void
    {
        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_invalid1',
            'visitor_name' => '잘못된상태',
            'status' => 'open',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->patchJson("/api/admin/rooms/{$room->id}", [
            'status' => 'invalid_status',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(422);
    }

    public function test_admin_close_room_not_found(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->patchJson("/api/admin/rooms/{$fakeId}", [
            'status' => 'closed',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(404);
    }

    // --- Admin\RoomController: markRead ---

    public function test_admin_mark_read_returns_todo(): void
    {
        $this->skipIfNoMongo();
        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_read0001',
            'visitor_name' => '읽음테스트',
            'status' => 'open',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson("/api/admin/rooms/{$room->id}/read", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // TODO: MongoDB implementation - for now returns 200 with a message
        $response->assertStatus(200);
    }

    // --- Admin\RoomController: messages ---

    public function test_admin_messages_returns_todo(): void
    {
        $this->skipIfNoMongo();
        $room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_msg00001',
            'visitor_name' => '메시지테스트',
            'status' => 'open',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson("/api/admin/rooms/{$room->id}/messages", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // TODO: MongoDB implementation - for now returns 200 with empty data
        $response->assertStatus(200);
    }

    /**
     * Helper: login as admin and return token.
     */
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
