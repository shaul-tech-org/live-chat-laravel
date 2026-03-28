<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoomResource;
use App\Http\Responses\ApiResponse;
use App\Services\RoomService;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly RoutingService $routingService,
    ) {}

    /**
     * 수동 배정 — 특정 상담원에게 대화를 배정한다.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid',
        ]);

        $room = $this->roomService->findOrFail($id);
        $agent = $this->routingService->manualAssign($room, $validated['agent_id']);

        return ApiResponse::success(new RoomResource($room->refresh()->load('assignedAgent')));
    }

    /**
     * 이관 — 다른 상담원에게 대화를 넘긴다.
     */
    public function transfer(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|uuid',
        ]);

        $room = $this->roomService->findOrFail($id);
        $agent = $this->routingService->transfer($room, $validated['agent_id']);

        return ApiResponse::success(new RoomResource($room->refresh()->load('assignedAgent')));
    }

    /**
     * 배정 해제 — 대기열로 복귀시킨다.
     */
    public function unassign(string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);
        $this->routingService->unassign($room);

        return ApiResponse::success(new RoomResource($room->refresh()->load('assignedAgent')));
    }
}
