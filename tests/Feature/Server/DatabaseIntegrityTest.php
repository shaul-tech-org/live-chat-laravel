<?php

namespace Tests\Feature\Server;

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Feedback;
use App\Models\FaqEntry;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_soft_delete_sets_deleted_at(): void
    {
        $tenant = Tenant::create([
            'name' => 'Soft Delete Test',
            'api_key' => 'ck_live_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $tenantId = $tenant->id;
        $tenant->delete();

        $trashed = Tenant::withTrashed()->find($tenantId);
        $this->assertNotNull($trashed);
        $this->assertNotNull($trashed->deleted_at);
    }

    public function test_soft_deleted_records_not_in_default_query(): void
    {
        $tenant = Tenant::create([
            'name' => 'Hidden Tenant',
            'api_key' => 'ck_live_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $tenantId = $tenant->id;
        $tenant->delete();

        $allTenants = Tenant::all();
        $ids = $allTenants->pluck('id')->toArray();
        $this->assertNotContains($tenantId, $ids);

        $withTrashed = Tenant::withTrashed()->find($tenantId);
        $this->assertNotNull($withTrashed);
    }

    public function test_uuid_primary_keys_format(): void
    {
        $tenant = Tenant::create([
            'name' => 'UUID Test Tenant',
            'api_key' => 'ck_live_' . bin2hex(random_bytes(16)),
            'owner_id' => 'test-owner',
        ]);

        $this->assertTrue(Str::isUuid($tenant->id));

        $room = ChatRoom::create([
            'tenant_id' => $tenant->id,
            'visitor_id' => 'v_uuid_test',
            'visitor_name' => 'UUID방문자',
            'status' => 'open',
        ]);

        $this->assertTrue(Str::isUuid($room->id));

        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'rating' => 5,
            'comment' => 'UUID 테스트',
        ]);

        $this->assertTrue(Str::isUuid($feedback->id));
    }

    public function test_all_tables_have_soft_deletes_column(): void
    {
        $tables = ['tenants', 'agents', 'chat_rooms', 'feedbacks', 'faq_entries'];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'deleted_at'),
                "Table '{$table}' is missing deleted_at column for soft deletes",
            );
        }
    }
}
