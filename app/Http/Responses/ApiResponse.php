<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function success(mixed $data = null, int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            return $data->additional(['success' => true])->response()->setStatusCode($status);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    public static function created(mixed $data = null): JsonResponse
    {
        return static::success($data, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(['success' => true], 204);
    }

    public static function paginated(ResourceCollection $collection): JsonResponse
    {
        return $collection->additional(['success' => true])->response();
    }

    public static function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
