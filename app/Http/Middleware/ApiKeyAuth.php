<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'API 키가 필요합니다.']], 401);
        }

        $tenant = Tenant::withoutTrashed()->where('api_key', $apiKey)->first();

        if (!$tenant) {
            return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => '유효하지 않은 API 키입니다.']], 401);
        }

        if (!$tenant->is_active) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => '비활성화된 테넌트입니다.']], 403);
        }

        $request->merge(['tenant_id' => $tenant->id, 'tenant' => $tenant]);
        return $next($request);
    }
}
