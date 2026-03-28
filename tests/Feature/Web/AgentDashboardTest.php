<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class AgentDashboardTest extends TestCase
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

    public function test_agent_dashboard_accessible_with_auth(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/agent');

        $response->assertStatus(200);
    }

    public function test_agent_dashboard_redirects_without_auth(): void
    {
        $response = $this->get('/agent');

        $response->assertRedirect('/login');
    }

    public function test_agent_dashboard_contains_agent_ui_elements(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/agent');

        $response->assertStatus(200);
        $response->assertSee('상담원', false);
        $response->assertSee('내 대화', false);
        $response->assertSee('대기 중', false);
        $response->assertSee('로그아웃', false);
    }

    public function test_agent_dashboard_contains_alpine_function(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/agent');

        $response->assertStatus(200);
        $response->assertSee('agentDashboard()', false);
    }

    public function test_agent_dashboard_has_link_to_admin(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/agent');

        $response->assertStatus(200);
        $response->assertSee('관리자', false);
    }
}
