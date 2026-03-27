<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'api_key' => 'ck_live_test_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $this->assertNotNull($tenant->id);
        $this->assertEquals('Test Tenant', $tenant->name);
        $this->assertDatabaseHas('tenants', ['name' => 'Test Tenant']);
    }

    public function test_soft_delete_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Delete Test',
            'api_key' => 'ck_live_del_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $tenant->delete();

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]); // still in DB
        $this->assertNull(Tenant::find($tenant->id)); // not found by default query
        $this->assertNotNull(Tenant::withTrashed()->find($tenant->id)); // found with trashed
    }

    public function test_widget_config_is_json(): void
    {
        $tenant = Tenant::create([
            'name' => 'JSON Test',
            'api_key' => 'ck_live_json_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
            'widget_config' => ['primary_color' => '#4F46E5', 'position' => 'bottom-right'],
        ]);

        $fresh = Tenant::find($tenant->id);
        $this->assertIsArray($fresh->widget_config);
        $this->assertEquals('#4F46E5', $fresh->widget_config['primary_color']);
    }
}
