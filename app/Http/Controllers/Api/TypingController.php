<?php

namespace App\Http\Controllers\Api;

use App\Events\TypingStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendTypingRequest;
use App\Http\Responses\ApiResponse;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;

class TypingController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function store(SendTypingRequest $request, string $id): JsonResponse
    {
        $room = $this->roomService->findForTenant($id, $request->get('tenant_id'));
        $validated = $request->validated();

        broadcast(new TypingStarted(
            room_id: $room->id,
            sender_type: $validated['sender_type'],
            sender_name: $validated['sender_name'],
        ))->toOthers();

        return ApiResponse::success(['status' => 'ok']);
    }
}
