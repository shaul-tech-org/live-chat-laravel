<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('tenants'));
    }

    public function test_tenants_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('tenants', [
            'id', 'name', 'domain', 'api_key', 'widget_config',
            'telegram_chat_id', 'auto_reply_message', 'owner_id',
            'is_active', 'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_agents_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('agents'));
    }

    public function test_agents_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('agents', [
            'id', 'tenant_id', 'user_id', 'name', 'email', 'role',
            'is_online', 'is_active', 'last_seen_at', 'deleted_at',
        ]));
    }

    public function test_chat_rooms_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('chat_rooms'));
    }

    public function test_chat_rooms_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('chat_rooms', [
            'id', 'tenant_id', 'visitor_id', 'visitor_name',
            'status', 'assigned_agent_id', 'closed_at', 'deleted_at',
        ]));
    }

    public function test_feedbacks_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('feedbacks'));
    }

    public function test_feedbacks_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('feedbacks', [
            'id', 'tenant_id', 'room_id', 'rating', 'comment', 'deleted_at',
        ]));
    }

    public function test_faq_entries_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('faq_entries'));
    }

    public function test_faq_entries_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('faq_entries', [
            'id', 'tenant_id', 'keyword', 'answer', 'is_active', 'deleted_at',
        ]));
    }
}
