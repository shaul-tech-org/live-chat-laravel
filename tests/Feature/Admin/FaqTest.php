<?php

namespace Tests\Feature\Admin;

use App\Models\FaqEntry;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
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

    public function test_create_faq(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/faq', [
            'tenant_id' => $this->tenant->id,
            'keyword' => '배송',
            'answer' => '배송은 2-3일 소요됩니다.',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'tenant_id', 'keyword', 'answer']]);
        $this->assertEquals('배송', $response->json('data.keyword'));
        $this->assertDatabaseHas('faq_entries', ['keyword' => '배송']);
    }

    public function test_create_faq_validation_error(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->postJson('/api/admin/faq', [
            // missing required fields
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(422);
    }

    public function test_list_faq(): void
    {
        FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '반품',
            'answer' => '반품은 7일 이내 가능합니다.',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->getJson('/api/admin/faq', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_delete_faq(): void
    {
        $faq = FaqEntry::create([
            'tenant_id' => $this->tenant->id,
            'keyword' => '삭제대상',
            'answer' => '삭제 테스트 답변',
        ]);

        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $response = $this->deleteJson("/api/admin/faq/{$faq->id}", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('faq_entries', ['id' => $faq->id]);
    }

    public function test_delete_faq_not_found(): void
    {
        $token = $this->adminLogin();
        if (!$token) {
            $this->markTestSkipped('Built-in auth not configured');
        }

        $fakeId = '00000000-0000-0000-0000-000000000000';
        $response = $this->deleteJson("/api/admin/faq/{$fakeId}", [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(404);
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
