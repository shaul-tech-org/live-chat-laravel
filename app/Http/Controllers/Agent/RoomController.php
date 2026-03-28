<?php

namespace App\Http\Controllers\Agent;

use App\Events\TypingStarted;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Http\Responses\ApiResponse;
use App\Models\ChatRoom;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Services\AgentService;
use App\Services\ChatService;
use App\Services\RoomService;
use App\Services\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly ChatService $chatService,
        private readonly AgentService $agentService,
        private readonly RoutingService $routingService,
        private readonly MessageRepositoryInterface $messageRepo,
    ) {}

    /**
     * 상담원에게 배정된 방 + 미배정 열린 방 목록
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->get('auth_user');
        $agentId = $authUser['id'] ?? null;

        // 내 대화: assigned_agent_id 가 현재 유저인 방 (open)
        $myRooms = ChatRoom::where('assigned_agent_id', $agentId)
            ->where('status', 'open')
            ->orderByDesc('updated_at')
            ->get();

        // 대기 중: 미배정 open 방
        $waitingRooms = ChatRoom::whereNull('assigned_agent_id')
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->get();

        // 종료된 내 대화 (최근 20개)
        $closedRooms = ChatRoom::where('assigned_agent_id', $agentId)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit(20)
            ->get();

        return ApiResponse::success([
            'my_rooms' => RoomResource::collection($myRooms),
            'waiting_rooms' => RoomResource::collection($waitingRooms),
            'closed_rooms' => RoomResource::collection($closedRooms),
        ]);
    }

    /**
     * 대화 가져오기 — 미배정 방을 현재 상담원에게 배정
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $authUser = $request->get('auth_user');
        $agentId = $authUser['id'] ?? null;
        $room = $this->roomService->findOrFail($id);

        if ($room->assigned_agent_id && $room->assigned_agent_id !== $agentId) {
            return ApiResponse::error('ALREADY_ASSIGNED', '이미 다른 상담원에게 배정된 방입니다.', 409);
        }

        // DB에 등록된 Agent가 있으면 RoutingService를 사용하고,
        // 없으면(built-in admin 등) 직접 배정
        $agent = \App\Models\Agent::where('id', $agentId)->first();
        if ($agent) {
            $this->routingService->manualAssign($room, $agentId);
        } else {
            $room->assigned_agent_id = $agentId;
            $room->save();
        }

        return ApiResponse::success(new RoomResource($room->refresh()));
    }

    /**
     * 다른 상담원에게 전달
     */
    public function transfer(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
        ]);

        $room = $this->roomService->findOrFail($id);

        // DB에 등록된 Agent가 있으면 RoutingService를 사용하고,
        // 없으면 직접 배정
        $agent = \App\Models\Agent::where('id', $validated['agent_id'])->first();
        if ($agent) {
            $this->routingService->transfer($room, $validated['agent_id']);
        } else {
            $room->assigned_agent_id = $validated['agent_id'];
            $room->save();
        }

        return ApiResponse::success(new RoomResource($room->refresh()));
    }

    /**
     * 대화 종료
     */
    public function close(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->updateStatus($id, 'closed');

        return ApiResponse::success(new RoomResource($room));
    }

    /**
     * 메시지 목록
     */
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

    /**
     * 메시지 전송
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);
        $authUser = $request->get('auth_user');

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $message = $this->chatService->sendMessage($room, [
            'sender_type' => 'agent',
            'sender_name' => $authUser['name'] ?? '상담사',
            'content' => $validated['content'],
            'content_type' => 'text',
        ]);

        return ApiResponse::success(new MessageResource($message));
    }

    /**
     * 타이핑 표시
     */
    public function sendTyping(Request $request, string $id): JsonResponse
    {
        $room = $this->roomService->findOrFail($id);
        $authUser = $request->get('auth_user');

        broadcast(new TypingStarted(
            room_id: $room->id,
            sender_type: 'agent',
            sender_name: $authUser['name'] ?? '상담사',
        ))->toOthers();

        return ApiResponse::success(['status' => 'ok']);
    }

    /**
     * 상담원 목록 (전달용)
     */
    public function agents(Request $request): JsonResponse
    {
        $agents = $this->agentService->listAll(100);

        return ApiResponse::success(AgentResource::collection($agents));
    }
}
