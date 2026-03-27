<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Http\Responses\ApiResponse;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    public function index(): JsonResponse
    {
        $tenants = $this->tenantService->listAll();

        return ApiResponse::paginated(TenantResource::collection($tenants));
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());
        $resource = new TenantResource($tenant);
        $resource->showApiKey = true;

        return ApiResponse::created($resource);
    }

    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->tenantService->update($id, $request->validated());

        return ApiResponse::success(new TenantResource($tenant));
    }

    public function rotateKey(string $id): JsonResponse
    {
        $data = $this->tenantService->rotateApiKey($id);

        return ApiResponse::success($data);
    }
}
