<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddReactionRequest;
use App\Http\Resources\ReactionResource;
use App\Http\Responses\ApiResponse;
use App\Services\ReactionService;
use Illuminate\Http\JsonResponse;

class ReactionController extends Controller
{
    public function __construct(
        private readonly ReactionService $reactionService,
    ) {}

    public function store(AddReactionRequest $request, string $id): JsonResponse
    {
        $reaction = $this->reactionService->addReaction(
            $id,
            $request->get('tenant_id'),
            $request->validated(),
        );

        return ApiResponse::created(new ReactionResource($reaction));
    }
}
