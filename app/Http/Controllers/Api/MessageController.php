<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Responses\ApiResponse;
use App\Services\ChatService;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly RoomService $roomService,
    ) {}

    public function store(SendMessageRequest $request, string $id): JsonResponse
    {
        $room = $this->roomService->findForTenant($id, $request->get('tenant_id'));
        $message = $this->chatService->sendMessage($room, $request->validated());

        return ApiResponse::created(new MessageResource($message));
    }

    public function index(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findForTenant($id, $request->get('tenant_id'));

        $limit = min((int) $request->query('limit', 50), 100);
        $before = $request->query('before');

        $messages = $this->chatService->getHistory($room->id, $limit, $before);

        return ApiResponse::success([
            'data' => MessageResource::collection($messages),
            'meta' => [
                'room_id' => $room->id,
                'count' => $messages->count(),
            ],
        ]);
    }
}
