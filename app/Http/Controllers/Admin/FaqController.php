<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateFaqRequest;
use App\Http\Resources\FaqResource;
use App\Http\Responses\ApiResponse;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService,
    ) {}

    public function index(): JsonResponse
    {
        $faqs = $this->faqService->listAll();

        return ApiResponse::paginated(FaqResource::collection($faqs));
    }

    public function store(CreateFaqRequest $request): JsonResponse
    {
        $faq = $this->faqService->create($request->validated());

        return ApiResponse::created(new FaqResource($faq));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->faqService->delete($id);

        return ApiResponse::success(['message' => 'FAQ가 삭제되었습니다.']);
    }
}
