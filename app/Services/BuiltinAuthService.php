<?php

namespace App\Services;

class BuiltinAuthService
{
    private string $email;
    private string $passwordHash;
    private array $tokens = [];

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
        $this->tokens[$token] = [
            'id' => 'admin-builtin',
            'email' => $email,
            'name' => '관리자',
            'roles' => ['admin'],
            'expires_at' => time() + 86400,
        ];
        return $token;
    }

    public function verify(string $token): ?array
    {
        if (!isset($this->tokens[$token])) return null;
        $session = $this->tokens[$token];
        if (time() > $session['expires_at']) {
            unset($this->tokens[$token]);
            return null;
        }
        return $session;
    }
}
