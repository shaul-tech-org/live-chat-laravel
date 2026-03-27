<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BuiltinAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly BuiltinAuthService $authService,
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $token = $this->authService->login($validated['email'], $validated['password']);

        if (!$token) {
            return redirect()->route('login')
                ->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.'])
                ->withInput(['email' => $validated['email']]);
        }

        return redirect()->route('admin.dashboard')
            ->withCookie(cookie('shaul_access_token', $token, 1440, '/', null, false, true));
    }

    public function logout(): RedirectResponse
    {
        return redirect()->route('login')
            ->withCookie(cookie('shaul_access_token', '', -1));
    }
}
