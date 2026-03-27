<?php

namespace Tests\Unit\Services;

use App\Services\BuiltinAuthService;
use PHPUnit\Framework\TestCase;

class BuiltinAuthServiceTest extends TestCase
{
    private BuiltinAuthService $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = new BuiltinAuthService('admin@test.com', 'secret123');
    }

    public function test_login_with_valid_credentials(): void
    {
        $token = $this->auth->login('admin@test.com', 'secret123');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function test_login_with_invalid_email(): void
    {
        $this->assertNull($this->auth->login('wrong@test.com', 'secret123'));
    }

    public function test_login_with_invalid_password(): void
    {
        $this->assertNull($this->auth->login('admin@test.com', 'wrong'));
    }

    public function test_verify_valid_token(): void
    {
        $token = $this->auth->login('admin@test.com', 'secret123');
        $user = $this->auth->verify($token);
        $this->assertNotNull($user);
        $this->assertEquals('admin@test.com', $user['email']);
        $this->assertContains('admin', $user['roles']);
    }

    public function test_verify_invalid_token(): void
    {
        $this->assertNull($this->auth->verify('invalid-token'));
    }

    public function test_token_is_unique_per_login(): void
    {
        $token1 = $this->auth->login('admin@test.com', 'secret123');
        $token2 = $this->auth->login('admin@test.com', 'secret123');
        $this->assertNotEquals($token1, $token2);
    }

    public function test_disabled_when_no_credentials(): void
    {
        $auth = new BuiltinAuthService('', '');
        $this->assertFalse($auth->isEnabled());
    }

    public function test_enabled_when_credentials_set(): void
    {
        $this->assertTrue($this->auth->isEnabled());
    }
}
