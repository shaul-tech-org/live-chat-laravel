<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // Fallback: read encrypted cookie and decrypt
        if (!$token) {
            $encryptedCookie = $request->cookie('shaul_access_token');
            if ($encryptedCookie) {
                try {
                    $token = Crypt::decryptString($encryptedCookie);
                } catch (\Exception $e) {
                    // Cookie might be plain text (non-encrypted) or invalid
                    $token = $encryptedCookie;
                }
            }
        }

        if (!$token) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => '인증이 필요합니다.']], 401);
        }

        // Try built-in auth
        $builtinAuth = app(\App\Services\BuiltinAuthService::class);
        if ($builtinAuth->isEnabled()) {
            $user = $builtinAuth->verify($token);
            if ($user) {
                $request->merge(['auth_user' => $user]);
                return $next($request);
            }
        }

        // Try Keycloak auth (AUTH_API_URL must be configured)
        $keycloakAuth = app(\App\Services\KeycloakAuthService::class);
        if ($keycloakAuth->isEnabled()) {
            $user = $keycloakAuth->verify($token);
            if ($user) {
                $request->merge(['auth_user' => $user]);
                return $next($request);
            }
        }

        return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => '유효하지 않은 토큰입니다.']], 401);
    }
}
