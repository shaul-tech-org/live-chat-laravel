<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Repositories\Contracts\AgentRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pusher\Pusher;

class BroadcastAuthService
{
    public function __construct(
        private readonly BuiltinAuthService $authService,
        private readonly TenantRepositoryInterface $tenantRepo,
        private readonly RoomRepositoryInterface $roomRepo,
        private readonly AgentRepositoryInterface $agentRepo,
    ) {}

    public function authenticateChannel(Request $request): JsonResponse
    {
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        if (preg_match('/^private-chat\.(.+)$/', $channelName, $matches)) {
            return $this->authorizeChatChannel($request, $socketId, $channelName, $matches[1]);
        }

        if (preg_match('/^private-admin\.(.+)$/', $channelName, $matches)) {
            return $this->authorizeAdminChannel($request, $socketId, $channelName, $matches[1]);
        }

        throw new ForbiddenException('알 수 없는 채널입니다.');
    }

    private function authorizeChatChannel(Request $request, string $socketId, string $channelName, string $roomId): JsonResponse
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        if ($apiKey) {
            $tenant = $this->tenantRepo->findByApiKey($apiKey);
            if (!$tenant || !$tenant->is_active) {
                throw new UnauthorizedException('유효하지 않은 API 키입니다.');
            }

            $room = $this->roomRepo->findByIdAndTenant($roomId, $tenant->id);
            if (!$room) {
                throw new ForbiddenException('채팅방에 접근할 수 없습니다.');
            }

            return $this->signChannel($socketId, $channelName);
        }

        $token = $request->bearerToken();
        if ($token) {
            $user = $this->authService->verify($token);
            if (!$user) {
                throw new UnauthorizedException('유효하지 않은 토큰입니다.');
            }

            $room = $this->roomRepo->findById($roomId);
            if (!$room) {
                throw new NotFoundException('채팅방을 찾을 수 없습니다.');
            }

            return $this->signChannel($socketId, $channelName);
        }

        throw new UnauthorizedException('인증이 필요합니다.');
    }

    private function authorizeAdminChannel(Request $request, string $socketId, string $channelName, string $tenantId): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token) {
            throw new UnauthorizedException('인증이 필요합니다.');
        }

        $user = $this->authService->verify($token);
        if (!$user) {
            throw new UnauthorizedException('유효하지 않은 토큰입니다.');
        }

        $tenant = $this->tenantRepo->findById($tenantId);
        if (!$tenant || !$tenant->is_active) {
            throw new NotFoundException('테넌트를 찾을 수 없습니다.');
        }

        $agent = $this->agentRepo->findByTenantAndEmail($tenantId, $user['email']);
        if (!$agent || !$agent->is_active) {
            throw new ForbiddenException('해당 테넌트에 접근할 수 없습니다.');
        }

        return $this->signChannel($socketId, $channelName);
    }

    private function signChannel(string $socketId, string $channelName): JsonResponse
    {
        $pusher = new Pusher(
            config('broadcasting.connections.reverb.key'),
            config('broadcasting.connections.reverb.secret'),
            config('broadcasting.connections.reverb.app_id'),
        );

        $auth = $pusher->authorizeChannel($channelName, $socketId);

        return response()->json(json_decode($auth, true));
    }
}
