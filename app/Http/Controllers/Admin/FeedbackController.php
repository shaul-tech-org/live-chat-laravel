<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Http\Responses\ApiResponse;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $feedbacks = $this->feedbackService->listAll($perPage);
        $avgRating = $this->feedbackService->averageRating();

        return ApiResponse::success([
            'data' => FeedbackResource::collection($feedbacks->items()),
            'avg_rating' => round($avgRating, 1),
            'current_page' => $feedbacks->currentPage(),
            'last_page' => $feedbacks->lastPage(),
            'per_page' => $feedbacks->perPage(),
            'total' => $feedbacks->total(),
        ]);
    }
}
