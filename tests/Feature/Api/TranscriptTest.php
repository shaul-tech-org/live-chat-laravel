<?php

namespace Tests\Feature\Api;

use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TranscriptTest extends TestCase
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
            'visitor_id' => 'v_transcript',
            'visitor_name' => '트랜스크립트방문자',
            'status' => 'closed',
        ]);
    }

    public function test_request_transcript(): void
    {
        $this->skipIfNoMongo();
        $response = $this->postJson("/api/rooms/{$this->room->id}/transcript", [
            'email' => 'visitor@example.com',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['room_id', 'email', 'status']]);
    }

    public function test_request_transcript_without_email(): void
    {
        $response = $this->postJson("/api/rooms/{$this->room->id}/transcript", [], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422);
    }

    public function test_request_transcript_invalid_email(): void
    {
        $response = $this->postJson("/api/rooms/{$this->room->id}/transcript", [
            'email' => 'not-an-email',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_request_transcript_room_not_found(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->postJson("/api/rooms/{$fakeId}/transcript", [
            'email' => 'visitor@example.com',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(404);
    }
}
