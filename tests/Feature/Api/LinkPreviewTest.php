<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkPreviewTest extends TestCase
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

    public function test_link_preview_valid_url(): void
    {
        Http::fake([
            'https://example.com' => Http::response(
                '<html><head>'
                . '<meta property="og:title" content="Example Title">'
                . '<meta property="og:description" content="Example Description">'
                . '<meta property="og:image" content="https://example.com/image.jpg">'
                . '</head><body></body></html>',
                200
            ),
        ]);

        $response = $this->getJson('/api/link-preview?url=' . urlencode('https://example.com'), [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['title', 'description', 'image', 'url'])
            ->assertJsonPath('title', 'Example Title')
            ->assertJsonPath('description', 'Example Description')
            ->assertJsonPath('image', 'https://example.com/image.jpg');
    }

    public function test_link_preview_without_url(): void
    {
        $response = $this->getJson('/api/link-preview', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422);
    }

    public function test_link_preview_invalid_url(): void
    {
        $response = $this->getJson('/api/link-preview?url=not-a-url', [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422);
    }
}
