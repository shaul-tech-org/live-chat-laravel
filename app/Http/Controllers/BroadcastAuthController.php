<?php

namespace App\Http\Controllers;

use App\Http\Requests\BroadcastAuthRequest;
use App\Services\BroadcastAuthService;
use Illuminate\Http\JsonResponse;

class BroadcastAuthController extends Controller
{
    public function __construct(
        private readonly BroadcastAuthService $broadcastAuthService,
    ) {}

    public function authenticate(BroadcastAuthRequest $request): JsonResponse
    {
        return $this->broadcastAuthService->authenticateChannel($request);
    }
}
