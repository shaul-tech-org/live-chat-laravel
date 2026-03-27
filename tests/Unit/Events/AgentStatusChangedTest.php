<?php

namespace Tests\Unit\Events;

use App\Events\AgentStatusChanged;
use Illuminate\Broadcasting\PrivateChannel;
use PHPUnit\Framework\TestCase;

class AgentStatusChangedTest extends TestCase
{
    public function test_broadcast_on_returns_admin_channel(): void
    {
        $event = new AgentStatusChanged(
            tenant_id: 'tenant-xyz',
            agent_id: 'agent-001',
            agent_name: '상담원',
            is_online: true,
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-admin.tenant-xyz', $channels[0]->name);
    }

    public function test_broadcast_as_returns_event_name(): void
    {
        $event = new AgentStatusChanged(
            tenant_id: 'tenant-xyz',
            agent_id: 'agent-001',
            agent_name: '상담원',
            is_online: false,
        );

        $this->assertEquals('agent.status.changed', $event->broadcastAs());
    }

    public function test_broadcast_with_returns_correct_payload(): void
    {
        $event = new AgentStatusChanged(
            tenant_id: 'tenant-xyz',
            agent_id: 'agent-001',
            agent_name: '상담원',
            is_online: true,
        );

        $data = $event->broadcastWith();
        $this->assertEquals('tenant-xyz', $data['tenant_id']);
        $this->assertEquals('agent-001', $data['agent_id']);
        $this->assertEquals('상담원', $data['agent_name']);
        $this->assertTrue($data['is_online']);
    }

    public function test_offline_status(): void
    {
        $event = new AgentStatusChanged(
            tenant_id: 'tenant-xyz',
            agent_id: 'agent-001',
            agent_name: '상담원',
            is_online: false,
        );

        $data = $event->broadcastWith();
        $this->assertFalse($data['is_online']);
    }

    public function test_implements_should_broadcast(): void
    {
        $event = new AgentStatusChanged(
            tenant_id: 'tenant-xyz',
            agent_id: 'agent-001',
            agent_name: '상담원',
            is_online: true,
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }
}
