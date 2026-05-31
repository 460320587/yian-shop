<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use PHPUnit\Framework\TestCase;

class BusinessExceptionTest extends TestCase
{
    public function test_exception_uses_error_code_message_by_default(): void
    {
        $exception = new BusinessException(ErrorCode::ORDER_NOT_FOUND);

        $this->assertSame('订单不存在', $exception->getMessage());
        $this->assertSame(4000, $exception->getCode());
        $this->assertSame(ErrorCode::ORDER_NOT_FOUND, $exception->getErrorCode());
    }

    public function test_exception_uses_custom_message(): void
    {
        $exception = new BusinessException(ErrorCode::PRODUCT_NOT_FOUND, '自定义商品错误');

        $this->assertSame('自定义商品错误', $exception->getMessage());
    }

    public function test_exception_returns_http_status(): void
    {
        $exception = new BusinessException(ErrorCode::UNAUTHORIZED);

        $this->assertSame(401, $exception->getHttpStatus());
    }

    public function test_exception_returns_200_for_business_errors(): void
    {
        $exception = new BusinessException(ErrorCode::ORDER_STATUS_INVALID);

        $this->assertSame(200, $exception->getHttpStatus());
    }
}
