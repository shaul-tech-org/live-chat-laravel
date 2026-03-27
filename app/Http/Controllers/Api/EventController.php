<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Mongo\WidgetEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class EventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'string', new Enum(EventType::class)],
            'page_url' => 'nullable|string|max:2048',
            'metadata' => 'nullable|array',
        ]);

        $event = WidgetEvent::create([
            'tenant_id' => $request->get('tenant_id'),
            'event_type' => $validated['event_type'],
            'page_url' => $validated['page_url'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip_hash' => hash('sha256', $request->ip()),
        ]);

        return response()->json([
            'id' => (string) $event->_id,
            'event_type' => $event->event_type,
            'page_url' => $event->page_url,
        ], 201);
    }
}
