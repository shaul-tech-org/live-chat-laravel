<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetConfigController extends Controller
{
    /**
     * GET /api/widget/config?api_key=KEY — 위젯 설정 조회 (공개 엔드포인트)
     */
    public function show(Request $request): JsonResponse
    {
        $apiKey = $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'error' => ['code' => 'UNAUTHORIZED', 'message' => 'API 키가 필요합니다.'],
            ], 401);
        }

        $tenant = Tenant::withoutTrashed()->where('api_key', $apiKey)->first();

        if (!$tenant) {
            return response()->json([
                'error' => ['code' => 'UNAUTHORIZED', 'message' => '유효하지 않은 API 키입니다.'],
            ], 401);
        }

        if (!$tenant->is_active) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => '비활성화된 테넌트입니다.'],
            ], 403);
        }

        return response()->json([
            'widget_config' => $tenant->widget_config ?? (object) [],
            'auto_reply_message' => $tenant->auto_reply_message,
            'tenant_name' => $tenant->name,
        ]);
    }
}
