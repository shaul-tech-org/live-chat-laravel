<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BuiltinAuthService
{
    private string $email;
    private string $passwordHash;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->passwordHash = $password ? hash('sha256', $password) : '';
    }

    public function isEnabled(): bool
    {
        return $this->email !== '' && $this->passwordHash !== '';
    }

    public function login(string $email, string $password): ?string
    {
        if (!$this->isEnabled()) return null;
        if ($email !== $this->email) return null;
        if (hash('sha256', $password) !== $this->passwordHash) return null;

        $token = bin2hex(random_bytes(32));
        Cache::put('auth_token:' . $token, [
            'id' => 'admin-builtin',
            'email' => $email,
            'name' => '관리자',
            'roles' => ['admin'],
        ], 86400);

        return $token;
    }

    public function verify(string $token): ?array
    {
        return Cache::get('auth_token:' . $token);
    }
}
