<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_route_without_token_returns_401(): void
    {
        $this->getJson('/api/admin/rooms')->assertStatus(401);
    }

    public function test_admin_route_with_valid_builtin_token(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'email' => config('chat.admin_email'),
            'password' => config('chat.admin_password'),
        ]);
        if ($login->status() !== 200) {
            $this->markTestSkipped('Built-in auth not configured');
        }
        $token = $login->json('data.accessToken');
        $this->getJson('/api/admin/rooms', ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(200);
    }

    public function test_admin_route_with_invalid_token_returns_401(): void
    {
        $this->getJson('/api/admin/rooms', ['Authorization' => 'Bearer invalid'])
            ->assertStatus(401);
    }
}
