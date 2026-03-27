<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_config_with_valid_key(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'Widget Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-wid',
            'widget_config' => [
                'primary_color' => '#4F46E5',
                'position' => 'bottom-right',
                'business_hours' => [
                    'timezone' => 'Asia/Seoul',
                    'schedule' => [
                        'mon' => ['09:00', '18:00'],
                        'tue' => ['09:00', '18:00'],
                    ],
                ],
            ],
            'auto_reply_message' => '현재 영업시간이 아닙니다.',
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['widget_config', 'auto_reply_message']])
            ->assertJsonPath('data.widget_config.primary_color', '#4F46E5')
            ->assertJsonPath('data.auto_reply_message', '현재 영업시간이 아닙니다.');
    }

    public function test_get_config_with_invalid_key(): void
    {
        $response = $this->getJson('/api/widget/config?api_key=invalid_key_12345');
        $response->assertStatus(401);
    }

    public function test_get_config_without_key(): void
    {
        $response = $this->getJson('/api/widget/config');
        $response->assertStatus(401);
    }

    public function test_get_config_with_inactive_tenant(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'Inactive Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-ina',
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");
        $response->assertStatus(403);
    }

    public function test_get_config_returns_empty_config_when_not_set(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'Empty Config Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-emp',
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['widget_config', 'auto_reply_message']]);
    }
}
