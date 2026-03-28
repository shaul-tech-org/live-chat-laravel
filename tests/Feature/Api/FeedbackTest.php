<?php

namespace Tests\Feature\Api;

use App\Models\ChatRoom;
use App\Models\Feedback;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $apiKey;
    private Tenant $tenant;
    private ChatRoom $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
        ]);
        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_feedback1',
            'visitor_name' => '피드백방문자',
            'status' => 'open',
        ]);
    }

    // --- Api\FeedbackController: store ---

    public function test_create_feedback_with_valid_data(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'room_id' => $this->room->id,
            'rating' => 5,
            'comment' => '아주 좋았습니다!',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'tenant_id', 'room_id', 'rating', 'comment']]);
        $this->assertEquals(5, $response->json('data.rating'));
        $this->assertDatabaseHas('feedbacks', ['room_id' => $this->room->id, 'rating' => 5]);
    }

    public function test_create_feedback_with_invalid_rating_too_high(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'room_id' => $this->room->id,
            'rating' => 6,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_create_feedback_with_invalid_rating_too_low(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'room_id' => $this->room->id,
            'rating' => 0,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_create_feedback_without_rating(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'room_id' => $this->room->id,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_create_feedback_without_room_id(): void
    {
        $response = $this->postJson('/api/feedbacks', [
            'rating' => 3,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    // --- Admin\FeedbackController: index ---

    public function test_admin_list_feedbacks(): void
    {
        Feedback::create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $this->room->id,
            'rating' => 4,
            'comment' => '좋았습니다',
        ]);
        Feedback::create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $this->room->id,
            'rating' => 2,
            'comment' => '보통이었습니다',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/feedbacks', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['data', 'avg_rating']]);
        $this->assertEquals(3.0, $response->json('data.avg_rating'));
    }

    public function test_admin_list_feedbacks_paginated(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/feedbacks?page=1&per_page=10', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['data', 'avg_rating']]);
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
