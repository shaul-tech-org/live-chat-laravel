<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FetchLinkPreviewRequest;
use App\Http\Responses\ApiResponse;
use App\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;

class LinkPreviewController extends Controller
{
    public function __construct(
        private readonly LinkPreviewService $linkPreviewService,
    ) {}

    public function show(FetchLinkPreviewRequest $request): JsonResponse
    {
        $data = $this->linkPreviewService->fetch($request->validated('url'));

        return ApiResponse::success($data);
    }
}
