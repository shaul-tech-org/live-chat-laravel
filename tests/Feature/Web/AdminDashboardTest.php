<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    private string $adminToken = '';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'chat.admin_email' => 'admin@test.com',
            'chat.admin_password' => 'test-password',
        ]);
        $this->app->singleton(\App\Services\BuiltinAuthService::class, function () {
            return new \App\Services\BuiltinAuthService('admin@test.com', 'test-password');
        });
        $authService = app(\App\Services\BuiltinAuthService::class);
        $this->adminToken = $authService->login('admin@test.com', 'test-password');
    }

    public function test_dashboard_contains_six_tabs(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('채팅', false);
        $response->assertSee('테넌트', false);
        $response->assertSee('상담원', false);
        $response->assertSee('피드백', false);
        $response->assertSee('FAQ', false);
        $response->assertSee('통계', false);
    }

    public function test_dashboard_contains_header_controls(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('로그아웃', false);
    }

    public function test_dashboard_includes_vite_assets(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertStatus(200);
    }
}
