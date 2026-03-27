<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class LoginFlowTest extends TestCase
{
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
    }

    public function test_login_page_has_email_and_password_fields(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('email', false);
        $response->assertSee('password', false);
    }

    public function test_web_login_with_valid_credentials_redirects_to_admin(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'test-password',
        ]);

        $response->assertRedirect('/admin');
        $response->assertCookie('shaul_access_token');
    }

    public function test_web_login_with_invalid_credentials_redirects_back(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors();
    }

    public function test_web_login_validation_requires_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'test-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_logout_clears_cookie_and_redirects(): void
    {
        $response = $this->withCookie('shaul_access_token', 'some-token')
            ->post('/logout');

        $response->assertRedirect('/login');
        $response->assertCookie('shaul_access_token', '');
    }
}
