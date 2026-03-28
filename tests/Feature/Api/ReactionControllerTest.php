<?php

namespace Tests\Feature\Api;

use App\Events\ReactionAdded;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReactionControllerTest extends TestCase
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
            'api_key' => 'reaction-test-key',
            'owner_id' => 'test-owner',
            'is_active' => true,
        ]);

        $this->room = ChatRoom::create([
            'tenant_id' => $this->tenant->id,
            'visitor_id' => 'v_reaction',
            'visitor_name' => '방문자',
            'status' => 'open',
        ]);
    }

    public function test_reaction_creates_and_broadcasts(): void
    {
        $this->skipIfNoMongo();
        Event::fake([ReactionAdded::class]);

        $response = $this->postJson('/api/rooms/' . $this->room->id . '/reactions', [
            'message_id' => 'msg-123',
            'emoji' => '👍',
            'user_id' => 'v_reaction',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'data' => ['id', 'room_id', 'message_id', 'emoji', 'user_id']]);
        $response->assertJson(['data' => [
            'room_id' => $this->room->id,
            'message_id' => 'msg-123',
            'emoji' => '👍',
        ]]);

        Event::assertDispatched(ReactionAdded::class, function ($event) {
            return $event->room_id === $this->room->id
                && $event->message_id === 'msg-123'
                && $event->emoji === '👍';
        });
    }

    public function test_reaction_requires_all_fields(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/reactions', [
            'emoji' => '👍',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(422);
    }

    public function test_reaction_room_not_found(): void
    {
        $fakeUuid = '00000000-0000-0000-0000-000000000000';
        $response = $this->postJson('/api/rooms/' . $fakeUuid . '/reactions', [
            'message_id' => 'msg-123',
            'emoji' => '👍',
            'user_id' => 'v_reaction',
        ], [
            'X-API-Key' => $this->tenant->api_key,
        ]);

        $response->assertStatus(404);
    }

    public function test_reaction_requires_api_key(): void
    {
        $response = $this->postJson('/api/rooms/' . $this->room->id . '/reactions', [
            'message_id' => 'msg-123',
            'emoji' => '👍',
            'user_id' => 'v_reaction',
        ]);

        $response->assertStatus(401);
    }
}
