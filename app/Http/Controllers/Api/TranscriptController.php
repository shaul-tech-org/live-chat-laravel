<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function store(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $room = ChatRoom::find($id);

        if (!$room) {
            return response()->json(['message' => '채팅방을 찾을 수 없습니다.'], 404);
        }

        // TODO: Fetch messages from MongoDB and send email
        // For now, return the transcript request confirmation

        return response()->json([
            'room_id' => $room->id,
            'email' => $validated['email'],
            'status' => 'queued',
            'message' => '대화 내역이 이메일로 전송될 예정입니다.',
        ]);
    }
}
