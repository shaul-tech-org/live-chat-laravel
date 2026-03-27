<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Http\Responses\ApiResponse;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    public function store(CreateFeedbackRequest $request): JsonResponse
    {
        $feedback = $this->feedbackService->create($request->get('tenant_id'), $request->validated());

        return ApiResponse::created(new FeedbackResource($feedback));
    }
}
