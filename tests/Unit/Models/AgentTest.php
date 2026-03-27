<?php

namespace Tests\Unit\Models;

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

    public function test_agent_casts_booleans(): void
    {
        $agent = new Agent();
        $casts = $agent->getCasts();
        $this->assertEquals('boolean', $casts['is_online']);
        $this->assertEquals('boolean', $casts['is_active']);
    }
}
