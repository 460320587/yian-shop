<?php

declare(strict_types=1);

namespace App\Support\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('daily')->info('API请求', [
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'request_id' => $request->header('X-Request-Id'),
        ]);

        return $response;
    }
}
