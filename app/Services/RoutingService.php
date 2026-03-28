<?php

namespace App\Services;

use App\Enums\AssignmentMethod;
use App\Events\SystemMessage;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Agent;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Cache;

class RoutingService
{
    /**
     * 라운드로빈 방식으로 온라인 상담원에게 자동 배정한다.
     * 온라인 상담원이 없으면 null 을 반환 (대기열 유지).
     */
    public function autoAssign(ChatRoom $room): ?Agent
    {
        $onlineAgents = Agent::where('tenant_id', $room->tenant_id)
            ->where('is_online', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($onlineAgents->isEmpty()) {
            return null;
        }

        $cacheKey = "routing:rr:{$room->tenant_id}";
        $lastIndex = (int) Cache::get($cacheKey, -1);
        $nextIndex = ($lastIndex + 1) % $onlineAgents->count();
        Cache::put($cacheKey, $nextIndex, now()->addHours(24));

        $agent = $onlineAgents[$nextIndex];

        $room->assigned_agent_id = $agent->id;
        $room->assignment_method = AssignmentMethod::Auto;
        $room->save();

        broadcast(new SystemMessage(
            room_id: $room->id,
            tenant_id: $room->tenant_id,
            content: "{$agent->name}님이 상담을 시작합니다.",
            type: 'assignment',
        ));

        return $agent;
    }

    /**
     * 관리자 또는 상담원이 수동으로 배정한다.
     */
    public function manualAssign(ChatRoom $room, string $agentId): Agent
    {
        $agent = $this->findActiveAgent($agentId, $room->tenant_id);

        $room->assigned_agent_id = $agent->id;
        $room->assignment_method = AssignmentMethod::Manual;
        $room->save();

        broadcast(new SystemMessage(
            room_id: $room->id,
            tenant_id: $room->tenant_id,
            content: "{$agent->name}님에게 상담이 배정되었습니다.",
            type: 'assignment',
        ));

        return $agent;
    }

    /**
     * 다른 상담원에게 이관한다.
     */
    public function transfer(ChatRoom $room, string $targetAgentId): Agent
    {
        if ($room->assigned_agent_id === $targetAgentId) {
            throw new ForbiddenException('현재 담당 상담원에게는 이관할 수 없습니다.');
        }

        $targetAgent = $this->findActiveAgent($targetAgentId, $room->tenant_id);
        $previousAgent = $room->assignedAgent;

        $room->assigned_agent_id = $targetAgent->id;
        $room->assignment_method = AssignmentMethod::Manual;
        $room->save();

        $fromName = $previousAgent?->name ?? '미배정';
        broadcast(new SystemMessage(
            room_id: $room->id,
            tenant_id: $room->tenant_id,
            content: "상담이 {$fromName}에서 {$targetAgent->name}님에게 이관되었습니다.",
            type: 'transfer',
        ));

        return $targetAgent;
    }

    /**
     * 배정 해제 (대기열로 복귀).
     */
    public function unassign(ChatRoom $room): void
    {
        $previousAgent = $room->assignedAgent;

        $room->assigned_agent_id = null;
        $room->assignment_method = null;
        $room->save();

        $agentName = $previousAgent?->name ?? '상담원';
        broadcast(new SystemMessage(
            room_id: $room->id,
            tenant_id: $room->tenant_id,
            content: "{$agentName}님의 배정이 해제되었습니다. 대기열로 복귀합니다.",
            type: 'unassignment',
        ));
    }

    /**
     * 해당 테넌트에 속한 활성 상담원을 조회한다.
     */
    private function findActiveAgent(string $agentId, string $tenantId): Agent
    {
        $agent = Agent::where('id', $agentId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$agent) {
            throw new NotFoundException('해당 테넌트에 속한 활성 상담원을 찾을 수 없습니다.');
        }

        return $agent;
    }
}
