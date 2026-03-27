<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Http\Responses\ApiResponse;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentService $agentService,
    ) {}

    public function index(): JsonResponse
    {
        $agents = $this->agentService->listAll();

        return ApiResponse::paginated(AgentResource::collection($agents));
    }

    public function online(): JsonResponse
    {
        $count = $this->agentService->onlineCount();

        return ApiResponse::success([
            'online_count' => $count,
        ]);
    }

    public function store(CreateAgentRequest $request): JsonResponse
    {
        $agent = $this->agentService->create($request->validated());

        return ApiResponse::created(new AgentResource($agent));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->agentService->delete($id);

        return ApiResponse::success(['message' => '상담원이 삭제되었습니다.']);
    }
}
