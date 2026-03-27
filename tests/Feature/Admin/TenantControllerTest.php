<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    // --- index ---

    public function test_list_tenants_masks_api_key(): void
    {
        $apiKey = 'ck_live_abcdef1234567890abcdef1234567890';
        Tenant::create([
            'name' => 'Masked Key Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-1',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/tenants', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $tenants = $response->json('data');
        $this->assertNotEmpty($tenants);

        $firstTenant = $tenants[0];
        $this->assertArrayHasKey('api_key_masked', $firstTenant);
        $this->assertEquals('ck_live_...', $firstTenant['api_key_masked']);
        $this->assertArrayNotHasKey('api_key', $firstTenant);
    }

    // --- store ---

    public function test_create_tenant_with_auto_generated_key(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/tenants', [
            'name' => '새 테넌트',
            'domain' => 'https://example.com',
            'owner_id' => 'owner-new',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'domain', 'api_key']]);

        $apiKey = $response->json('data.api_key');
        $this->assertStringStartsWith('ck_live_', $apiKey);
        $this->assertEquals(40, strlen($apiKey)); // ck_live_ (8) + 32 hex
    }

    public function test_create_tenant_validation(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        // Missing required name
        $response = $this->postJson('/api/admin/tenants', [
            'domain' => 'https://example.com',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(422);
    }

    // --- update ---

    public function test_update_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Before Update',
            'api_key' => 'ck_live_' . bin2hex(random_bytes(16)),
            'owner_id' => 'owner-upd',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->patchJson("/api/admin/tenants/{$tenant->id}", [
            'name' => 'After Update',
            'domain' => 'https://updated.com',
            'widget_config' => ['primary_color' => '#FF0000'],
            'auto_reply_message' => '자동 응답 메시지입니다.',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'After Update')
            ->assertJsonPath('data.domain', 'https://updated.com');

        $fresh = Tenant::find($tenant->id);
        $this->assertEquals('After Update', $fresh->name);
        $this->assertEquals('#FF0000', $fresh->widget_config['primary_color']);
        $this->assertEquals('자동 응답 메시지입니다.', $fresh->auto_reply_message);
    }

    public function test_update_tenant_not_found(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->patchJson("/api/admin/tenants/{$fakeId}", [
            'name' => 'Does Not Exist',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(404);
    }

    // --- rotateKey ---

    public function test_rotate_key_generates_new_key(): void
    {
        $oldKey = 'ck_live_' . bin2hex(random_bytes(16));
        $tenant = Tenant::create([
            'name' => 'Rotate Key Tenant',
            'api_key' => $oldKey,
            'owner_id' => 'owner-rot',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson("/api/admin/tenants/{$tenant->id}/rotate-key", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['api_key']]);

        $newKey = $response->json('data.api_key');
        $this->assertNotEquals($oldKey, $newKey);
        $this->assertStringStartsWith('ck_live_', $newKey);
        $this->assertEquals(40, strlen($newKey));

        // DB should have the new key
        $fresh = Tenant::find($tenant->id);
        $this->assertEquals($newKey, $fresh->api_key);
    }

    public function test_rotate_key_not_found(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->postJson("/api/admin/tenants/{$fakeId}/rotate-key", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404);
    }

    // --- auth ---

    public function test_tenant_routes_require_admin_auth(): void
    {
        $this->getJson('/api/admin/tenants')->assertStatus(401);
        $this->postJson('/api/admin/tenants', ['name' => 'test'])->assertStatus(401);
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
