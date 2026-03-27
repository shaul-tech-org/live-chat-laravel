<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $feedbacks = Feedback::orderByDesc('created_at')->paginate($perPage);

        $avgRating = Feedback::avg('rating') ?? 0;

        return response()->json([
            'data' => $feedbacks->items(),
            'avg_rating' => round((float) $avgRating, 1),
            'current_page' => $feedbacks->currentPage(),
            'last_page' => $feedbacks->lastPage(),
            'per_page' => $feedbacks->perPage(),
            'total' => $feedbacks->total(),
        ]);
    }
}
