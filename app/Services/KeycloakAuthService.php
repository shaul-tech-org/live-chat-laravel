<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeycloakAuthService
{
    private string $authApiUrl;

    public function __construct(string $authApiUrl)
    {
        $this->authApiUrl = rtrim($authApiUrl, '/');
    }

    /**
     * Whether Keycloak auth is enabled (AUTH_API_URL configured).
     */
    public function isEnabled(): bool
    {
        return $this->authApiUrl !== '';
    }

    /**
     * Verify a bearer token against the auth API.
     *
     * Returns user info array on success, null on failure.
     */
    public function verify(string $token): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get("{$this->authApiUrl}/api/auth/verify");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data) || empty($data['id'])) {
                return null;
            }

            return [
                'id' => $data['id'],
                'email' => $data['email'] ?? '',
                'name' => $data['name'] ?? '',
                'roles' => $data['roles'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::warning('Keycloak auth verification failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
