<?php

namespace App\Http\Middleware;

use App\Services\BuiltinAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminWebAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('shaul_access_token');

        if (!$token) {
            return redirect()->route('login');
        }

        $authService = app(BuiltinAuthService::class);
        if ($authService->isEnabled()) {
            $user = $authService->verify($token);
            if ($user) {
                $request->merge(['auth_user' => $user]);
                return $next($request);
            }
        }

        return redirect()->route('login');
    }
}
