<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('admin')
                ->group(__DIR__.'/../routes/admin.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Support\Middleware\ForceJsonMiddleware::class,
            \App\Support\Middleware\RequestIdMiddleware::class,
            \App\Support\Middleware\ApiLogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\App\Exceptions\BusinessException $e) {
            return \App\Support\ApiResponse::error($e->getErrorCode(), $e->getMessage());
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return \App\Support\ApiResponse::error(
                \App\Support\ErrorCode::VALIDATION_ERROR,
                '请求参数校验失败',
                $e->errors()
            );
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return \App\Support\ApiResponse::error(\App\Support\ErrorCode::UNAUTHORIZED, '未登录或登录已过期');
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return \App\Support\ApiResponse::error(\App\Support\ErrorCode::FORBIDDEN, '无权访问该资源');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return \App\Support\ApiResponse::error(\App\Support\ErrorCode::FORBIDDEN, '无权访问该资源');
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \App\Support\ApiResponse::error(\App\Support\ErrorCode::NOT_FOUND, '请求的资源不存在');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return \App\Support\ApiResponse::error(\App\Support\ErrorCode::NOT_FOUND, '请求的资源不存在');
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return \App\Support\ApiResponse::error(
                    \App\Support\ErrorCode::SYSTEM_ERROR,
                    app()->isProduction() ? '系统繁忙，请稍后重试' : $e->getMessage()
                );
            }
        });
    })->create();
