<?php

namespace Tests\Unit\Services;

use App\Enums\AssignmentMethod;
use App\Events\SystemMessage;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use App\Services\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoutingService $routingService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routingService = app(RoutingService::class);
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);
    }

    // ── autoAssign ──

    public function test_auto_assign_returns_null_when_no_online_agents(): void
    {
        Event::fake([SystemMessage::class]);

        $room = $this->createRoom();

        $result = $this->routingService->autoAssign($room);

        $this->assertNull($result);
        $this->assertNull($room->refresh()->assigned_agent_id);
        Event::assertNotDispatched(SystemMessage::class);
    }

    public function test_auto_assign_assigns_to_online_agent(): void
    {
        Event::fake([SystemMessage::class]);

        $agent = $this->createAgent(['is_online' => true, 'name' => '상담원A']);
        $room = $this->createRoom();

        $result = $this->routingService->autoAssign($room);

        $this->assertNotNull($result);
        $this->assertEquals($agent->id, $result->id);
        $this->assertEquals($agent->id, $room->refresh()->assigned_agent_id);
        $this->assertEquals(AssignmentMethod::Auto, $room->assignment_method);

        Event::assertDispatched(SystemMessage::class, function (SystemMessage $event) use ($room) {
            return $event->room_id === $room->id && $event->type === 'assignment';
        });
    }

    public function test_auto_assign_round_robin_distributes_evenly(): void
    {
        Event::fake([SystemMessage::class]);
        Cache::flush();

        $agentA = $this->createAgent(['is_online' => true, 'name' => 'A상담원']);
        $agentB = $this->createAgent(['is_online' => true, 'name' => 'B상담원']);

        $room1 = $this->createRoom();
        $result1 = $this->routingService->autoAssign($room1);

        $room2 = $this->createRoom();
        $result2 = $this->routingService->autoAssign($room2);

        // 두 상담원이 각각 하나씩 배정 받아야 함
        $assignedIds = [$result1->id, $result2->id];
        $this->assertContains($agentA->id, $assignedIds);
        $this->assertContains($agentB->id, $assignedIds);
        $this->assertNotEquals($result1->id, $result2->id);
    }

    public function test_auto_assign_skips_offline_agents(): void
    {
        Event::fake([SystemMessage::class]);

        $this->createAgent(['is_online' => false, 'name' => '오프라인']);
        $onlineAgent = $this->createAgent(['is_online' => true, 'name' => '온라인']);
        $room = $this->createRoom();

        $result = $this->routingService->autoAssign($room);

        $this->assertNotNull($result);
        $this->assertEquals($onlineAgent->id, $result->id);
    }

    public function test_auto_assign_skips_inactive_agents(): void
    {
        Event::fake([SystemMessage::class]);

        $this->createAgent(['is_online' => true, 'is_active' => false, 'name' => '비활성']);
        $activeAgent = $this->createAgent(['is_online' => true, 'is_active' => true, 'name' => '활성']);
        $room = $this->createRoom();

        $result = $this->routingService->autoAssign($room);

        $this->assertNotNull($result);
        $this->assertEquals($activeAgent->id, $result->id);
    }

    // ── manualAssign ──

    public function test_manual_assign_sets_agent_and_method(): void
    {
        Event::fake([SystemMessage::class]);

        $agent = $this->createAgent(['name' => '수동배정']);
        $room = $this->createRoom();

        $result = $this->routingService->manualAssign($room, $agent->id);

        $this->assertEquals($agent->id, $result->id);
        $this->assertEquals($agent->id, $room->refresh()->assigned_agent_id);
        $this->assertEquals(AssignmentMethod::Manual, $room->assignment_method);

        Event::assertDispatched(SystemMessage::class, function (SystemMessage $event) use ($room) {
            return $event->room_id === $room->id && $event->type === 'assignment';
        });
    }

    public function test_manual_assign_throws_when_agent_not_found(): void
    {
        $room = $this->createRoom();

        $this->expectException(NotFoundException::class);
        $this->routingService->manualAssign($room, '00000000-0000-0000-0000-000000000000');
    }

    public function test_manual_assign_throws_when_agent_inactive(): void
    {
        $agent = $this->createAgent(['is_active' => false, 'name' => '비활성']);
        $room = $this->createRoom();

        $this->expectException(NotFoundException::class);
        $this->routingService->manualAssign($room, $agent->id);
    }

    public function test_manual_assign_throws_when_agent_different_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'other-owner',
            'is_active' => true,
        ]);
        $agent = Agent::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => 'other-user',
            'name' => '다른 테넌트 상담원',
            'email' => 'other@example.com',
            'role' => 'agent',
            'is_active' => true,
        ]);

        $room = $this->createRoom();

        $this->expectException(NotFoundException::class);
        $this->routingService->manualAssign($room, $agent->id);
    }

    // ── transfer ──

    public function test_transfer_changes_agent(): void
    {
        Event::fake([SystemMessage::class]);

        $agentA = $this->createAgent(['name' => '상담원A']);
        $agentB = $this->createAgent(['name' => '상담원B']);
        $room = $this->createRoom(['assigned_agent_id' => $agentA->id]);

        $result = $this->routingService->transfer($room, $agentB->id);

        $this->assertEquals($agentB->id, $result->id);
        $this->assertEquals($agentB->id, $room->refresh()->assigned_agent_id);
        $this->assertEquals(AssignmentMethod::Manual, $room->assignment_method);

        Event::assertDispatched(SystemMessage::class, function (SystemMessage $event) use ($room) {
            return $event->room_id === $room->id && $event->type === 'transfer';
        });
    }

    public function test_transfer_throws_when_same_agent(): void
    {
        $agent = $this->createAgent(['name' => '상담원']);
        $room = $this->createRoom(['assigned_agent_id' => $agent->id]);

        $this->expectException(ForbiddenException::class);
        $this->routingService->transfer($room, $agent->id);
    }

    public function test_transfer_throws_when_target_not_found(): void
    {
        $agent = $this->createAgent(['name' => '상담원']);
        $room = $this->createRoom(['assigned_agent_id' => $agent->id]);

        $this->expectException(NotFoundException::class);
        $this->routingService->transfer($room, '00000000-0000-0000-0000-000000000000');
    }

    // ── unassign ──

    public function test_unassign_clears_agent_and_method(): void
    {
        Event::fake([SystemMessage::class]);

        $agent = $this->createAgent(['name' => '상담원']);
        $room = $this->createRoom([
            'assigned_agent_id' => $agent->id,
            'assignment_method' => 'manual',
        ]);

        $this->routingService->unassign($room);

        $room->refresh();
        $this->assertNull($room->assigned_agent_id);
        $this->assertNull($room->assignment_method);

        Event::assertDispatched(SystemMessage::class, function (SystemMessage $event) use ($room) {
            return $event->room_id === $room->id && $event->type === 'unassignment';
        });
    }

    // ── helpers ──

    private function createRoom(array $overrides = []): ChatRoom
    {
        return ChatRoom::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_' . substr(bin2hex(random_bytes(4)), 0, 8),
            'visitor_name' => '방문자',
            'status' => 'open',
        ], $overrides));
    }

    private function createAgent(array $overrides = []): Agent
    {
        static $counter = 0;
        $counter++;

        return Agent::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'user_id' => 'user-' . $counter . '-' . bin2hex(random_bytes(4)),
            'name' => '상담원' . $counter,
            'email' => "agent{$counter}@example.com",
            'role' => 'agent',
            'is_online' => false,
            'is_active' => true,
        ], $overrides));
    }
}
