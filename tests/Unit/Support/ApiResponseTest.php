<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_returns_json_response(): void
    {
        $response = ApiResponse::success(['id' => 1, 'name' => 'test']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SUCCESS->value, $data['code']);
        $this->assertSame('成功', $data['message']);
        $this->assertSame(['id' => 1, 'name' => 'test'], $data['data']);
    }

    public function test_success_with_custom_message(): void
    {
        $response = ApiResponse::success(['id' => 1], '自定义消息');

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SUCCESS->value, $data['code']);
        $this->assertSame('自定义消息', $data['message']);
    }

    public function test_success_with_empty_data(): void
    {
        $response = ApiResponse::success();

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SUCCESS->value, $data['code']);
        $this->assertSame([], $data['data']);
    }

    public function test_error_returns_json_response(): void
    {
        $response = ApiResponse::error(ErrorCode::SYSTEM_ERROR);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(500, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SYSTEM_ERROR->value, $data['code']);
        $this->assertSame('系统错误', $data['message']);
        $this->assertNull($data['data']);
    }

    public function test_error_with_custom_message(): void
    {
        $response = ApiResponse::error(ErrorCode::BAD_REQUEST, '参数校验失败');

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::BAD_REQUEST->value, $data['code']);
        $this->assertSame('参数校验失败', $data['message']);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_error_with_custom_data(): void
    {
        $response = ApiResponse::error(ErrorCode::VALIDATION_ERROR, null, ['field' => 'required']);

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::VALIDATION_ERROR->value, $data['code']);
        $this->assertSame(['field' => 'required'], $data['data']);
    }

    public function test_paginated_returns_json_response_with_meta(): void
    {
        $items = collect([['id' => 1], ['id' => 2], ['id' => 3]]);
        $paginator = new LengthAwarePaginator(
            $items,
            100,
            10,
            2
        );

        $response = ApiResponse::paginated($paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SUCCESS->value, $data['code']);
        $this->assertCount(3, $data['data']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertSame(100, $data['meta']['total']);
        $this->assertSame(10, $data['meta']['per_page']);
        $this->assertSame(2, $data['meta']['current_page']);
        $this->assertSame(10, $data['meta']['last_page']);
    }

    public function test_paginated_with_custom_message(): void
    {
        $items = collect([]);
        $paginator = new LengthAwarePaginator($items, 0, 10, 1);

        $response = ApiResponse::paginated($paginator, '获取列表成功');

        $data = $response->getData(true);
        $this->assertSame('获取列表成功', $data['message']);
    }

    public function test_no_content_returns_204_response(): void
    {
        $response = ApiResponse::noContent();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertSame(ErrorCode::SUCCESS->value, $data['code']);
        $this->assertSame('成功', $data['message']);
    }

    public function test_response_structure_is_consistent(): void
    {
        $response = ApiResponse::success(['key' => 'value']);
        $data = $response->getData(true);

        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('data', $data);
    }
}
