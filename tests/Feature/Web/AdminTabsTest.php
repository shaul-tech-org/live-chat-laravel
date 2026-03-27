<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class AdminTabsTest extends TestCase
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

    public function test_tenants_tab_has_create_form(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('tenantsTab()', false);
        $response->assertSee('createTenant', false);
    }

    public function test_agents_tab_has_create_form(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('agentsTab()', false);
        $response->assertSee('createAgent', false);
    }

    public function test_faq_tab_has_create_form(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('faqTab()', false);
        $response->assertSee('createFaq', false);
    }

    public function test_feedbacks_tab_has_average_rating(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('feedbacksTab()', false);
        $response->assertSee('avgRating', false);
    }

    public function test_stats_tab_has_period_selector(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('statsTab()', false);
        $response->assertSee('changePeriod', false);
    }

    public function test_dashboard_has_echo_integration(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('Echo', false);
    }

    public function test_dashboard_has_dark_mode_toggle(): void
    {
        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertSee('darkMode', false);
    }

    public function test_mobile_chat_has_alpine_component(): void
    {
        $response = $this->get('/m/test-room-123');

        $response->assertStatus(200);
        $response->assertSee('mobileChat()', false);
        $response->assertSee('test-room-123', false);
    }

    public function test_demo_page_has_widget_script(): void
    {
        $response = $this->get('/demo');

        $response->assertStatus(200);
        $response->assertSee('widget.js', false);
    }
}
