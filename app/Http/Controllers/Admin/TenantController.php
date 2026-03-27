<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * GET /api/admin/tenants — 테넌트 목록 (API Key 마스킹)
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::orderByDesc('created_at')->paginate(20);

        $tenants->getCollection()->transform(function (Tenant $tenant) {
            $data = $tenant->toArray();
            $data['api_key_masked'] = substr($tenant->api_key, 0, 8) . '...';
            return $data;
        });

        return response()->json($tenants);
    }

    /**
     * POST /api/admin/tenants — 테넌트 생성 (API Key 자동 발급)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'domain' => 'nullable|string|max:255',
            'owner_id' => 'required|string|max:255',
            'widget_config' => 'nullable|array',
            'auto_reply_message' => 'nullable|string',
            'telegram_chat_id' => 'nullable|integer',
        ]);

        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));

        $tenant = Tenant::create([
            ...$validated,
            'api_key' => $apiKey,
        ]);

        // 생성 시에는 API Key를 한 번 노출
        $response = $tenant->toArray();
        $response['api_key'] = $apiKey;

        return response()->json($response, 201);
    }

    /**
     * PATCH /api/admin/tenants/{id} — 테넌트 정보 수정
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => '테넌트를 찾을 수 없습니다.'],
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'domain' => 'nullable|string|max:255',
            'widget_config' => 'nullable|array',
            'auto_reply_message' => 'nullable|string',
            'telegram_chat_id' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant->update($validated);

        return response()->json($tenant);
    }

    /**
     * POST /api/admin/tenants/{id}/rotate-key — API Key 재발급
     */
    public function rotateKey(string $id): JsonResponse
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => '테넌트를 찾을 수 없습니다.'],
            ], 404);
        }

        $newKey = 'ck_live_' . bin2hex(random_bytes(16));
        $tenant->update(['api_key' => $newKey]);

        return response()->json([
            'id' => $tenant->id,
            'api_key' => $newKey,
            'message' => 'API 키가 재발급되었습니다.',
        ]);
    }
}
