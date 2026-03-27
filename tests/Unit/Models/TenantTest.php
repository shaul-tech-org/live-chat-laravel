<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    public function test_tenant_fillable_attributes(): void
    {
        $tenant = new Tenant();
        $this->assertContains('name', $tenant->getFillable());
        $this->assertContains('api_key', $tenant->getFillable());
        $this->assertContains('widget_config', $tenant->getFillable());
    }

    public function test_tenant_casts_widget_config_to_array(): void
    {
        $tenant = new Tenant();
        $casts = $tenant->getCasts();
        $this->assertEquals('array', $casts['widget_config']);
    }

    public function test_tenant_casts_is_active_to_boolean(): void
    {
        $tenant = new Tenant();
        $casts = $tenant->getCasts();
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_tenant_uses_uuid_primary_key(): void
    {
        $tenant = new Tenant();
        $this->assertEquals('string', $tenant->getKeyType());
        $this->assertFalse($tenant->getIncrementing());
    }

    public function test_tenant_hides_api_key(): void
    {
        $tenant = new Tenant();
        $this->assertContains('api_key', $tenant->getHidden());
    }
}
