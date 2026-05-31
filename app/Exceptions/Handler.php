<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderApiException($e);
        }

        return parent::render($request, $e);
    }

    private function renderApiException(Throwable $e)
    {
        if ($e instanceof BusinessException) {
            return ApiResponse::error($e->getErrorCode(), $e->getMessage());
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                ErrorCode::VALIDATION_ERROR,
                '请求参数校验失败',
                $e->errors()
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(ErrorCode::UNAUTHORIZED, '未登录或登录已过期');
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error(ErrorCode::FORBIDDEN, '无权访问该资源');
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error(ErrorCode::NOT_FOUND, '请求的资源不存在');
        }

        return ApiResponse::error(
            ErrorCode::SYSTEM_ERROR,
            app()->isProduction() ? '系统繁忙，请稍后重试' : $e->getMessage()
        );
    }
}
