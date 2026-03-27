<?php

namespace App\Services;

use App\Models\Mongo\WidgetEvent;
use App\Repositories\Contracts\WidgetEventRepositoryInterface;
use Illuminate\Http\Request;

class WidgetEventService
{
    public function __construct(
        private readonly WidgetEventRepositoryInterface $eventRepo,
    ) {}

    public function create(string $tenantId, array $data, Request $request): WidgetEvent
    {
        return $this->eventRepo->create([
            'tenant_id' => $tenantId,
            'event_type' => $data['event_type'],
            'page_url' => $data['page_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'user_agent' => $request->userAgent(),
            'ip_hash' => hash('sha256', $request->ip()),
        ]);
    }
}
