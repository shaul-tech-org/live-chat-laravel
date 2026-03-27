<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BuiltinAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(Request $request, BuiltinAuthService $auth): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $token = $auth->login($validated['email'], $validated['password']);

        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '이메일 또는 비밀번호가 올바르지 않습니다.'],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => ['accessToken' => $token],
        ]);
    }
}
