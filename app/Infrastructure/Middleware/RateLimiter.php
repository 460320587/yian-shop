<?php

declare(strict_types=1);

namespace App\Infrastructure\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter as LaravelRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RateLimiter
{
    public function __construct(
        private readonly LaravelRateLimiter $limiter
    ) {
    }

    /**
     * 处理请求限流
     *
     * @param Request $request
     * @param Closure $next
     * @param string $key 限流标识前缀
     * @param int $maxAttempts 最大尝试次数
     * @param int $decayMinutes 衰减时间（分钟）
     * @return Response|JsonResponse|SymfonyResponse
     */
    public function handle(Request $request, Closure $next, string $key = 'default', int $maxAttempts = 60, int $decayMinutes = 1): Response|JsonResponse|SymfonyResponse
    {
        $rateKey = $this->resolveRateKey($request, $key);

        if ($this->limiter->tooManyAttempts($rateKey, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($request);
        }

        $this->limiter->hit($rateKey, $decayMinutes * 60);

        return $next($request);
    }

    /**
     * 解析限流键
     */
    private function resolveRateKey(Request $request, string $key): string
    {
        $identifier = $request->user()?->id ?? $request->ip() ?? 'unknown';
        return sprintf('rate_limit:%s:%s', $key, $identifier);
    }

    /**
     * 构建限流响应
     */
    private function buildTooManyAttemptsResponse(Request $request): JsonResponse|Response
    {
        $message = '请求过于频繁，请稍后再试';
        $code = 429;

        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse([
                'code' => $code,
                'message' => $message,
                'data' => null,
            ], $code);
        }

        return new Response($message, $code);
    }
}
