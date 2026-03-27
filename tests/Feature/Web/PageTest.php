<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class PageTest extends TestCase
{
    public function test_login_page_returns_200(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_demo_page_returns_200(): void
    {
        $response = $this->get('/demo');
        $response->assertStatus(200);
        $response->assertViewIs('pages.demo');
    }

    public function test_admin_redirects_to_login_without_auth(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_admin_returns_200_with_auth(): void
    {
        $this->setupAdminAuth();

        $response = $this->withCookie('shaul_access_token', $this->adminToken)
            ->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    public function test_mobile_chat_page_returns_200(): void
    {
        $response = $this->get('/m/test-room-id');
        $response->assertStatus(200);
        $response->assertViewIs('pages.mobile-chat');
    }

    public function test_home_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    private string $adminToken = '';

    private function setupAdminAuth(): void
    {
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
}
