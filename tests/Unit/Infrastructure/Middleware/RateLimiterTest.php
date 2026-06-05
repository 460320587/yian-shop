<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Middleware;

use Tests\TestCase;
use App\Infrastructure\Middleware\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Cache\RateLimiter as LaravelRateLimiter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RateLimiterTest extends TestCase
{
    public function test_middleware_exists(): void
    {
        $this->assertTrue(class_exists(RateLimiter::class), 'RateLimiter 中间件类必须存在');
    }

    public function test_middleware_allows_request_when_under_limit(): void
    {
        $middleware = new RateLimiter(app(LaravelRateLimiter::class));
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        }, 'test', 10, 1);

        $this->assertTrue($called, '未超限请求必须被放行');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_request_when_over_limit(): void
    {
        $middleware = new RateLimiter(app(LaravelRateLimiter::class));
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // 先打满限流桶
        for ($i = 0; $i < 10; $i++) {
            $middleware->handle($request, fn () => new Response('ok'), 'test', 10, 1);
        }

        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        }, 'test', 10, 1);

        $this->assertFalse($called, '超限请求必须被拦截');
        $this->assertEquals(429, $response->getStatusCode(), '必须返回 429 Too Many Requests');
    }

    public function test_middleware_returns_json_for_api_routes(): void
    {
        $middleware = new RateLimiter(app(LaravelRateLimiter::class));
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        for ($i = 0; $i < 10; $i++) {
            $middleware->handle($request, fn () => new Response('ok'), 'test', 10, 1);
        }

        $response = $middleware->handle($request, fn () => new Response('ok'), 'test', 10, 1);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $data, '限流响应必须包含 code');
        $this->assertArrayHasKey('message', $data, '限流响应必须包含 message');
    }

    public function test_middleware_uses_custom_key(): void
    {
        $middleware = new RateLimiter(app(LaravelRateLimiter::class));
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok');
        }, 'test', 10, 1);

        $this->assertTrue($called, '不同 IP 必须独立计数');
    }
}
