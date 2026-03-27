<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateRoomRequest;
use App\Http\Requests\Api\ListVisitorRoomsRequest;
use App\Http\Resources\RoomResource;
use App\Http\Responses\ApiResponse;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function store(CreateRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->create($request->get('tenant_id'), $request->validated());

        return ApiResponse::created(new RoomResource($room));
    }

    public function visitorRooms(ListVisitorRoomsRequest $request): JsonResponse
    {
        $rooms = $this->roomService->listByVisitor(
            $request->get('tenant_id'),
            $request->validated('visitor_id'),
        );

        return ApiResponse::paginated(RoomResource::collection($rooms));
    }
}
