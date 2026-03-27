<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|uuid',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'visitor_email' => 'nullable|email|max:255',
            'page_url' => 'nullable|string|max:2048',
        ]);

        $feedback = Feedback::create([
            'tenant_id' => $request->get('tenant_id'),
            'room_id' => $validated['room_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'visitor_email' => $validated['visitor_email'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
        ]);

        return response()->json($feedback, 201);
    }
}
