<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Support\Middleware\ForceJsonMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ForceJsonMiddlewareTest extends TestCase
{
    public function test_sets_accept_header_to_json(): void
    {
        $request = Request::create('/api/v1/health');
        $request->headers->set('Accept', 'text/html');
        $middleware = new ForceJsonMiddleware();

        $middleware->handle($request, function ($req) {
            $this->assertSame('application/json', $req->header('Accept'));
            return new Response();
        });
    }
}
