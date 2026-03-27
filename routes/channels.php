<?php

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Tenant;
use Illuminate\Support\Facades\Broadcast;

// Chat room — visitor (API key) or agent (admin auth)
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    // Channel auth is handled by BroadcastAuthController
    // This callback is only used when standard Laravel Auth is active
    $room = ChatRoom::withoutTrashed()->find($roomId);

    return $room !== null;
});

// Admin — receives all messages for a tenant
Broadcast::channel('admin.{tenantId}', function ($user, $tenantId) {
    // Channel auth is handled by BroadcastAuthController
    $tenant = Tenant::withoutTrashed()->where('id', $tenantId)->where('is_active', true)->first();

    if (!$tenant) {
        return false;
    }

    // Verify agent belongs to tenant
    $agent = Agent::withoutTrashed()
        ->where('tenant_id', $tenantId)
        ->where('is_active', true)
        ->first();

    return $agent !== null;
});
