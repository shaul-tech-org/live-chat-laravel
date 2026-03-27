<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadFileRequest;
use App\Http\Responses\ApiResponse;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function __construct(
        private readonly UploadService $uploadService,
    ) {}

    public function store(UploadFileRequest $request): JsonResponse
    {
        $result = $this->uploadService->store($request->file('file'));

        return ApiResponse::created($result);
    }
}
