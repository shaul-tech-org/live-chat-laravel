<?php

namespace Tests\Unit\Models;

use App\Enums\AgentRole;
use App\Models\Agent;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    public function test_agent_fillable_attributes(): void
    {
        $agent = new Agent();
        $this->assertContains('tenant_id', $agent->getFillable());
        $this->assertContains('role', $agent->getFillable());
        $this->assertContains('is_online', $agent->getFillable());
    }

    public function test_agent_casts_role_to_enum(): void
    {
        $agent = new Agent();
        $casts = $agent->getCasts();
        $this->assertEquals(AgentRole::class, $casts['role']);
    }

    public function test_agent_role_enum_values(): void
    {
        $this->assertEquals('admin', AgentRole::Admin->value);
        $this->assertEquals('agent', AgentRole::Agent->value);
    }

    public function test_agent_casts_booleans(): void
    {
        $agent = new Agent();
        $casts = $agent->getCasts();
        $this->assertEquals('boolean', $casts['is_online']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_agent_uses_uuid_primary_key(): void
    {
        $agent = new Agent();
        $this->assertEquals('string', $agent->getKeyType());
        $this->assertFalse($agent->getIncrementing());
    }
}
