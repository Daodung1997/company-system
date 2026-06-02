<?php

namespace App\Supports\Components\Response;

use ArrayObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ResponseFormat
 */
class ResponseFormat
{
    public function success(array $data = []): JsonResponse
    {
        return response()->json([
            'code' => JsonResponse::HTTP_OK,
            'data' => empty($data) ? new ArrayObject : $data,
        ]);
    }

    public function created(array $data = []): JsonResponse
    {
        return response()->json([
            'code' => JsonResponse::HTTP_CREATED,
            'data' => empty($data) ? new ArrayObject : $data,
        ], JsonResponse::HTTP_CREATED);
    }

    public function failure(array $data, int $status = 500): JsonResponse
    {
        return response()->json([
            'code' => $status,
            'messages' => $data,
        ], $status);
    }

    public function pagination(
        AnonymousResourceCollection $data, int $total, int $current, int $limit, array $metadata = []
    ): JsonResponse {
        return response()->json([
            'code' => JsonResponse::HTTP_OK,
            'data' => [
                'data' => $data,
                'total' => $total,
                'current_page' => $current,
                'limit' => $limit,
                'metadata' => empty($metadata) ? new ArrayObject : $metadata,
            ],
        ]);
    }

    public function notFound(?string $message = null): JsonResponse
    {
        return response()->json([
            'code' => JsonResponse::HTTP_NOT_FOUND,
            'messages' => [
                empty($message) ? __('common.not_found') : $message,
            ],
        ], JsonResponse::HTTP_NOT_FOUND);
    }

    public function resourceConflict(array $data, array $actions, int $status = 409): JsonResponse
    {
        return response()->json([
            'code' => $status,
            'messages' => $data,
            'resource_conflict_actions' => $actions,
        ], $status);
    }
}
