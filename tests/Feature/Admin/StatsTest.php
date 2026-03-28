<?php

namespace Tests\Feature\Admin;

use App\Models\ChatRoom;
use App\Models\Feedback;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
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

    public function test_get_stats_default_period(): void
    {
        // Create chat rooms
        ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_stat001',
            'visitor_name' => '통계방문자1',
            'status' => 'open',
        ]);
        ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_stat002',
            'visitor_name' => '통계방문자2',
            'status' => 'closed',
        ]);

        // Create feedbacks
        $room = ChatRoom::first();
        Feedback::create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $room->id,
            'rating' => 5,
        ]);
        Feedback::create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $room->id,
            'rating' => 3,
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/stats?period=7d', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['total_chats', 'avg_rating', 'daily']]);
        $this->assertEquals(2, $response->json('data.total_chats'));
        $this->assertEquals(4.0, $response->json('data.avg_rating'));
    }

    public function test_get_stats_empty_data(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/stats', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['total_chats', 'avg_rating', 'daily']]);
        $this->assertEquals(0, $response->json('data.total_chats'));
        $this->assertEquals(0, $response->json('data.avg_rating'));
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
