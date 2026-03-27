<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WidgetConfigResource;
use App\Http\Responses\ApiResponse;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetConfigController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenant = $this->tenantService->findByApiKeyOrFail($request->query('api_key', ''));

        return ApiResponse::success(new WidgetConfigResource($tenant));
    }
}
