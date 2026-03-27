<?php

namespace Tests\Feature\Security;

use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InjectionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'Injection Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_injection_test',
            'visitor_name' => '테스트 방문자',
            'status' => 'open',
        ]);
    }

    public function test_sql_injection_in_visitor_name(): void
    {
        $sqlPayload = "'; DROP TABLE tenants;--";

        $response = $this->postJson(
            '/api/rooms',
            ['visitor_name' => $sqlPayload],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
        $this->assertTrue(Tenant::where('id', $this->tenant->id)->exists());
    }

    public function test_nosql_injection_in_message_content(): void
    {
        $nosqlPayload = '{"$gt":""}';

        $response = $this->postJson(
            "/api/rooms/{$this->room->id}/messages",
            [
                'sender_type' => 'visitor',
                'sender_name' => '방문자',
                'content' => $nosqlPayload,
                'content_type' => 'text',
            ],
            ['X-API-Key' => $this->apiKey],
        );

        $response->assertStatus(201);
        $storedContent = $response->json('data.content');
        $this->assertIsString($storedContent);
    }

    public function test_sql_injection_in_search_query(): void
    {
        $sqlPayload = "'; DROP TABLE tenants;--";

        $response = $this->getJson(
            '/api/rooms?visitor_id=' . urlencode($sqlPayload),
            ['X-API-Key' => $this->apiKey],
        );

        $this->assertContains($response->status(), [200, 422]);
        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
        $this->assertTrue(Tenant::where('id', $this->tenant->id)->exists());
    }
}
