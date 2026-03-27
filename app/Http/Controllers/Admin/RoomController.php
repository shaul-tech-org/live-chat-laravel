<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = ChatRoom::orderByDesc('created_at')->paginate(20);
        return response()->json($rooms);
    }

    /**
     * PATCH /api/admin/rooms/{id} — 채팅방 상태 변경 (종료 등)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $room = ChatRoom::find($id);
        if (!$room) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => '채팅방을 찾을 수 없습니다.'],
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:open,closed',
        ]);

        $room->status = $validated['status'];
        if ($validated['status'] === 'closed' && !$room->closed_at) {
            $room->closed_at = now();
        }
        $room->save();

        return response()->json($room);
    }

    /**
     * POST /api/admin/rooms/{id}/read — 메시지 읽음 처리 (TODO: MongoDB)
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $room = ChatRoom::find($id);
        if (!$room) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => '채팅방을 찾을 수 없습니다.'],
            ], 404);
        }

        // TODO: MongoDB에서 해당 방의 메시지를 읽음 처리
        return response()->json([
            'success' => true,
            'message' => '읽음 처리 완료 (TODO: MongoDB 구현 필요)',
            'room_id' => $id,
        ]);
    }

    /**
     * GET /api/admin/rooms/{id}/messages — 메시지 목록 조회 (TODO: MongoDB)
     */
    public function messages(Request $request, string $id): JsonResponse
    {
        $room = ChatRoom::find($id);
        if (!$room) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => '채팅방을 찾을 수 없습니다.'],
            ], 404);
        }

        // TODO: MongoDB에서 메시지 조회
        return response()->json([
            'data' => [],
            'meta' => [
                'room_id' => $id,
                'message' => 'TODO: MongoDB 구현 필요',
            ],
        ]);
    }
}
