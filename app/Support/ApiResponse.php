<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(mixed $data = [], ?string $message = null, int $httpStatus = 200): JsonResponse
    {
        return new JsonResponse([
            'code' => ErrorCode::SUCCESS->value,
            'message' => $message ?? ErrorCode::SUCCESS->message(),
            'data' => $data,
        ], $httpStatus);
    }

    public static function error(ErrorCode $errorCode, ?string $message = null, mixed $data = null, ?int $httpStatus = null): JsonResponse
    {
        return new JsonResponse([
            'code' => $errorCode->value,
            'message' => $message ?? $errorCode->message(),
            'data' => $data,
        ], $httpStatus ?? $errorCode->httpStatus());
    }

    public static function paginated(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        return new JsonResponse([
            'code' => ErrorCode::SUCCESS->value,
            'message' => $message ?? ErrorCode::SUCCESS->message(),
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public static function noContent(?string $message = null): JsonResponse
    {
        return new JsonResponse([
            'code' => ErrorCode::SUCCESS->value,
            'message' => $message ?? ErrorCode::SUCCESS->message(),
            'data' => null,
        ], 204);
    }
}
