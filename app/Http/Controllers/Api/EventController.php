<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitEventRequest;
use App\Http\Responses\ApiResponse;
use App\Services\WidgetEventService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private readonly WidgetEventService $eventService,
    ) {}

    public function store(SubmitEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create(
            $request->get('tenant_id'),
            $request->validated(),
            $request,
        );

        return ApiResponse::created([
            'id' => (string) $event->_id,
            'event_type' => $event->event_type,
            'page_url' => $event->page_url,
        ]);
    }
}
