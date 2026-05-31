<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Support\Middleware\RequestIdMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    public function test_generates_request_id_when_not_present(): void
    {
        $request = Request::create('/api/v1/health');
        $middleware = new RequestIdMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertNotNull($response->headers->get('X-Request-Id'));
        $this->assertTrue(
            \Illuminate\Support\Str::isUuid($response->headers->get('X-Request-Id'))
        );
    }

    public function test_uses_existing_request_id_from_header(): void
    {
        $request = Request::create('/api/v1/health');
        $request->headers->set('X-Request-Id', 'existing-request-id');
        $middleware = new RequestIdMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertSame('existing-request-id', $response->headers->get('X-Request-Id'));
    }
}
