<?php

namespace Tests\Feature\Api;

use App\Events\TypingStarted;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TypingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;
    private ChatRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test',
            'domain' => 'test.com',
            'api_key' => 'typing-test-key',
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_typing',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);
    }

    public function test_typing_broadcasts_event(): void
    {
        Event::fake([TypingStarted::class]);

        $response = $this->postJson('/api/rooms/' . $this->room->id . '/typing', [
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'data' => ['status' => 'ok']]);

        Event::assertDispatched(TypingStarted::class, function ($event) {
            return $event->room_id === $this->room->id
                && $event->sender_type === 'visitor'
                && $event->sender_name === '방문자';
        });
    }

    public function test_typing_requires_sender_type(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/typing', [
            'sender_name' => '방문자',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_typing_validates_sender_type(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/typing', [
            'sender_type' => 'invalid',
            'sender_name' => '방문자',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_typing_room_not_found(): void
    {
        $fakeUuid = '00000000-0000-0000-0000-000000000000';
        $response = $this->postJson('/api/rooms/' . $fakeUuid . '/typing', [
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(404);
    }

    public function test_typing_requires_api_key(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/typing', [
            'sender_type' => 'visitor',
            'sender_name' => '방문자',
        ]);

        $response->assertStatus(401);
    }
}
