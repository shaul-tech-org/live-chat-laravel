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
                'prechat_fields' => [
                    ['name' => 'name', 'label' => '이름', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => '이메일', 'type' => 'email', 'required' => false],
                    ['name' => 'phone', 'label' => '전화번호', 'type' => 'tel', 'required' => false],
                ],
                'business_hours' => [
                    'enabled' => true,
                    'timezone' => 'Asia/Seoul',
                    'schedule' => [
                        'mon' => ['start' => '09:00', 'end' => '18:00'],
                        'tue' => ['start' => '09:00', 'end' => '18:00'],
                        'wed' => ['start' => '09:00', 'end' => '18:00'],
                        'thu' => ['start' => '09:00', 'end' => '18:00'],
                        'fri' => ['start' => '09:00', 'end' => '18:00'],
                    ],
                    'offline_message' => '영업시간이 아닙니다. 이메일을 남겨주세요.',
                ],
            ],
            'auto_reply_message' => '현재 영업시간이 아닙니다.',
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => [
                'widget_config', 'prechat_fields', 'business_hours',
                'is_within_business_hours', 'auto_reply_message', 'agents_online',
            ]])
            ->assertJsonPath('data.widget_config.primary_color', '#4F46E5')
            ->assertJsonPath('data.auto_reply_message', '현재 영업시간이 아닙니다.')
            ->assertJsonPath('data.prechat_fields.0.name', 'name')
            ->assertJsonPath('data.business_hours.enabled', true);
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
            ->assertJsonStructure(['success', 'data' => [
                'widget_config', 'prechat_fields', 'is_within_business_hours',
                'auto_reply_message', 'agents_online',
            ]])
            ->assertJsonPath('data.is_within_business_hours', true) // 미설정 시 항상 운영중
            ->assertJsonCount(3, 'data.prechat_fields'); // 기본 3개 필드
    }

    public function test_get_config_includes_branding_settings(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'Branding Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-brand',
            'widget_config' => [
                'primary_color' => '#FF6B35',
                'position' => 'bottom-left',
                'title' => 'Help Center',
                'greeting' => 'How can we assist you?',
                'logo_url' => 'https://example.com/logo.png',
            ],
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonPath('data.widget_config.primary_color', '#FF6B35')
            ->assertJsonPath('data.widget_config.position', 'bottom-left')
            ->assertJsonPath('data.widget_config.title', 'Help Center')
            ->assertJsonPath('data.widget_config.greeting', 'How can we assist you?')
            ->assertJsonPath('data.widget_config.logo_url', 'https://example.com/logo.png');
    }

    public function test_get_config_branding_defaults_to_null_when_not_set(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'No Branding Tenant',
            'api_key' => $apiKey,
            'owner_id' => 'owner-nobrand',
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonPath('data.widget_config.primary_color', null)
            ->assertJsonPath('data.widget_config.position', null)
            ->assertJsonPath('data.widget_config.title', null)
            ->assertJsonPath('data.widget_config.greeting', null)
            ->assertJsonPath('data.widget_config.logo_url', null);
    }

    public function test_business_hours_disabled_returns_within_hours_true(): void
    {
        $apiKey = 'ck_live_' . bin2hex(random_bytes(16));
        Tenant::create([
            'name' => 'BH Disabled',
            'api_key' => $apiKey,
            'owner_id' => 'owner-bhd',
            'widget_config' => [
                'business_hours' => [
                    'enabled' => false,
                    'timezone' => 'Asia/Seoul',
                    'schedule' => [],
                ],
            ],
        ]);

        $response = $this->getJson("/api/widget/config?api_key={$apiKey}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_within_business_hours', true);
    }
}
