<?php

namespace Tests\Feature\Middleware;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
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
            'owner_id' => 'test',
        ]);
    }

    public function test_valid_api_key_passes(): void
    {
        $response = $this->postJson('/api/rooms', [
            'visitor_name' => 'Test',
        ], ['X-API-Key' => $this->apiKey]);
        $response->assertStatus(201);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/rooms', ['visitor_name' => 'Test']);
        $response->assertStatus(401);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/rooms', ['visitor_name' => 'Test'], ['X-API-Key' => 'invalid']);
        $response->assertStatus(401);
    }

    public function test_inactive_tenant_returns_403(): void
    {
        $tenant = Tenant::where('api_key', $this->apiKey)->first();
        $tenant->update(['is_active' => false]);
        $response = $this->postJson('/api/rooms', ['visitor_name' => 'Test'], ['X-API-Key' => $this->apiKey]);
        $response->assertStatus(403);
    }
}
