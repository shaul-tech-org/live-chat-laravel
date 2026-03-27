<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
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

    public function test_upload_valid_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('chat-image.jpg', 100, 100)->size(500);

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['file_url', 'file_name', 'file_size', 'mime_type']);
    }

    public function test_upload_file_too_large(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large-image.jpg')->size(11000); // 11MB

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_upload_invalid_mime_type(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malware.exe', 500, 'application/x-msdownload');

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_upload_without_file(): void
    {
        $response = $this->postJson('/api/upload', [], [
            'X-API-Key' => $this->apiKey,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_pdf_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJsonStructure(['file_url', 'file_name', 'file_size', 'mime_type']);
    }
}
