<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RequestTranscriptRequest;
use App\Http\Responses\ApiResponse;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;

class TranscriptController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
    ) {}

    public function store(RequestTranscriptRequest $request, string $id): JsonResponse
    {
        $room = $this->roomService->findForTenant($id, $request->get('tenant_id'));

        return ApiResponse::success([
            'room_id' => $room->id,
            'email' => $request->validated('email'),
            'status' => 'queued',
            'message' => '대화 내역이 이메일로 전송될 예정입니다.',
        ]);
    }
}
