<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function success(mixed $data = [], ?string $message = null, int $httpStatus = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $httpStatus);
    }

    public function error(ErrorCode $errorCode, ?string $message = null, mixed $data = null): JsonResponse
    {
        return ApiResponse::error($errorCode, $message, $data);
    }

    public function paginated(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        return ApiResponse::paginated($paginator, $message);
    }

    public function noContent(?string $message = null): JsonResponse
    {
        return ApiResponse::noContent($message);
    }
}
