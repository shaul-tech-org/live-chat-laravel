<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')->assertStatus(200)->assertJson(['status' => 'ok']);
    }

    public function test_login_with_valid_credentials(): void
    {
        if (!config('chat.admin_email') || !config('chat.admin_password')) {
            $this->markTestSkipped('Built-in auth not configured');
        }
        $this->postJson('/api/auth/login', [
            'email' => config('chat.admin_email'),
            'password' => config('chat.admin_password'),
        ])->assertStatus(200)->assertJsonStructure(['success', 'data' => ['accessToken']]);
    }

    public function test_login_with_wrong_password(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com', 'password' => 'wrong',
        ])->assertStatus(401);
    }

    public function test_login_with_missing_fields(): void
    {
        $this->postJson('/api/auth/login', [])->assertStatus(422);
    }
}
