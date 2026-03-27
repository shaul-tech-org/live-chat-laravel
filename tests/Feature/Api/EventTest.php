<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
        ]);
    }

    public function test_submit_event(): void
    {
        $response = $this->postJson('/api/events', [
            'event_type' => 'page_view',
            'page_url' => 'https://example.com/products',
            'metadata' => ['product_id' => '123'],
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'event_type', 'page_url']);
    }

    public function test_submit_event_without_event_type(): void
    {
        $response = $this->postJson('/api/events', [
            'page_url' => 'https://example.com',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_submit_event_invalid_event_type(): void
    {
        $response = $this->postJson('/api/events', [
            'event_type' => 'invalid_type',
            'page_url' => 'https://example.com',
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }
}
