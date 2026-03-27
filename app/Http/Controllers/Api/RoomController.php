<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_name' => 'nullable|string|max:100',
            'visitor_email' => 'nullable|email|max:255',
        ]);

        $room = ChatRoom::create([
            'tenant_id' => $request->get('tenant_id'),
            'visitor_id' => 'v_' . substr(bin2hex(random_bytes(4)), 0, 8),
            'visitor_name' => $validated['visitor_name'] ?? '방문자',
            'visitor_email' => $validated['visitor_email'] ?? null,
            'status' => 'open',
        ]);

        return response()->json($room, 201);
    }
}
