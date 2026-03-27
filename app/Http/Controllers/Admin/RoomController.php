<?php

namespace App\Http\Controllers\Admin;

use App\Events\TypingStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Services\ChatService;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly ChatService $chatService,
        private readonly MessageRepositoryInterface $messageRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $sort = $request->query('sort', 'newest');

        $rooms = $this->roomService->listAll($perPage, $sort);

        return ApiResponse::paginated(RoomResource::collection($rooms));
    }

    public function update(UpdateRoomRequest $request, string $id): JsonResponse
    {
        $room = $this->roomService->updateStatus($id, $request->validated('status'));

        return ApiResponse::success(new RoomResource($room));
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);
        $result = $this->messageRepo->markAsRead($room->id, 'visitor');

        return ApiResponse::success([
            'updated_count' => $result['count'],
            'last_read_message_id' => $result['last_id'],
            'room_id' => $room->id,
        ]);
    }

    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'sender_name' => 'nullable|string|max:100',
        ]);

        $message = $this->chatService->sendMessage($room, [
            'sender_type' => 'agent',
            'sender_name' => $validated['sender_name'] ?? '상담사',
            'content' => $validated['content'],
            'content_type' => 'text',
        ]);

        return ApiResponse::success(new MessageResource($message));
    }

    public function sendTyping(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);

        $validated = $request->validate([
            'sender_name' => 'nullable|string|max:100',
        ]);

        broadcast(new TypingStarted(
            room_id: $room->id,
            sender_type: 'agent',
            sender_name: $validated['sender_name'] ?? '상담사',
        ))->toOthers();

        return ApiResponse::success(['status' => 'ok']);
    }

    public function messages(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);

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
