<?php

namespace Tests\Feature\Security;

use App\Models\Tenant;
use App\Services\BuiltinAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = 'ck_live_test_' . bin2hex(random_bytes(16));
        $this->tenant = Tenant::create([
            'name' => 'Auth Test Tenant',
            'api_key' => $this->apiKey,
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);
    }

    public function test_widget_api_without_key_returns_401(): void
    {
        $response = $this->postJson('/api/rooms', [
            'visitor_name' => 'Test Visitor',
        ]);

        $response->assertStatus(401);
    }

    public function test_widget_api_with_invalid_key_returns_401(): void
    {
        $response = $this->postJson('/api/rooms', [
            'visitor_name' => 'Test Visitor',
        ], ['X-API-Key' => 'ck_live_invalid_key_does_not_exist']);

        $response->assertStatus(401);
    }

    public function test_admin_api_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/admin/rooms');

        $response->assertStatus(401);
    }

    public function test_admin_api_with_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/admin/rooms', [
            'Authorization' => 'Bearer invalid-token-12345',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_key_masked_in_tenant_list(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/tenants', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $tenants = $response->json('data');
        $this->assertNotEmpty($tenants);

        foreach ($tenants as $tenant) {
            $this->assertArrayNotHasKey('api_key', $tenant);
            $this->assertArrayHasKey('api_key_masked', $tenant);
            $this->assertStringEndsWith('...', $tenant['api_key_masked']);
        }
    }

    public function test_error_response_has_no_stack_trace(): void
    {
        $originalDebug = config('app.debug');
        config(['app.debug' => false]);

        $fakeId = 'not-a-valid-uuid-format-at-all';

        $response = $this->getJson(
            "/api/rooms/{$fakeId}/messages",
            ['X-API-Key' => $this->apiKey],
        );

        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('Stack trace', $responseContent);
        $this->assertStringNotContainsString('.php', $responseContent);
        $this->assertStringNotContainsString('vendor/', $responseContent);

        config(['app.debug' => $originalDebug]);
    }

    public function test_upload_exe_file_rejected(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malware.exe', 500, 'application/x-msdownload');

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
    }

    public function test_upload_oversized_file_rejected(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('huge-image.jpg')->size(11000);

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ], ['X-API-Key' => $this->apiKey]);

        $response->assertStatus(422);
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
