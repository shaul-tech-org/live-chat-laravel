<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MarkReadRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ReadReceiptService;
use Illuminate\Http\JsonResponse;

class ReadReceiptController extends Controller
{
    public function __construct(
        private readonly ReadReceiptService $readReceiptService,
    ) {}

    public function store(MarkReadRequest $request, string $id): JsonResponse
    {
        $result = $this->readReceiptService->markAsRead(
            $id,
            $request->get('tenant_id'),
            $request->validated(),
        );

        return ApiResponse::success($result);
    }
}
