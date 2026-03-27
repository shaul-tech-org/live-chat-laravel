<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->statsService->getStats($request->query('period', '7d'));

        return ApiResponse::success($data);
    }
}
