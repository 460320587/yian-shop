<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BaseControllerTest extends TestCase
{
    private BaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new class extends BaseController {};
    }

    public function test_success_returns_json_response(): void
    {
        $response = $this->controller->success(['id' => 1]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $response->getData(true)['code']);
    }

    public function test_error_returns_json_response(): void
    {
        $response = $this->controller->error(ErrorCode::NOT_FOUND);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(404, $response->getData(true)['code']);
    }

    public function test_paginated_returns_json_response(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 10, 1);
        $response = $this->controller->paginated($paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArrayHasKey('meta', $response->getData(true));
    }

    public function test_no_content_returns_204(): void
    {
        $response = $this->controller->noContent();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }
}
