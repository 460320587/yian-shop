<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ErrorCode;
use PHPUnit\Framework\TestCase;

class ErrorCodeTest extends TestCase
{
    public function test_common_error_codes_exist(): void
    {
        $this->assertSame(0, ErrorCode::SUCCESS->value);
        $this->assertSame(500, ErrorCode::SYSTEM_ERROR->value);
        $this->assertSame(400, ErrorCode::BAD_REQUEST->value);
        $this->assertSame(401, ErrorCode::UNAUTHORIZED->value);
        $this->assertSame(403, ErrorCode::FORBIDDEN->value);
        $this->assertSame(404, ErrorCode::NOT_FOUND->value);
        $this->assertSame(422, ErrorCode::VALIDATION_ERROR->value);
        $this->assertSame(429, ErrorCode::TOO_MANY_REQUESTS->value);
    }

    public function test_auth_error_codes_exist(): void
    {
        $this->assertSame(1000, ErrorCode::AUTH_LOGIN_FAILED->value);
        $this->assertSame(1001, ErrorCode::AUTH_TOKEN_EXPIRED->value);
        $this->assertSame(1002, ErrorCode::AUTH_TOKEN_INVALID->value);
        $this->assertSame(1003, ErrorCode::AUTH_LOGOUT_FAILED->value);
    }

    public function test_user_error_codes_exist(): void
    {
        $this->assertSame(2000, ErrorCode::USER_NOT_FOUND->value);
        $this->assertSame(2001, ErrorCode::USER_ALREADY_EXISTS->value);
        $this->assertSame(2002, ErrorCode::USER_PASSWORD_ERROR->value);
        $this->assertSame(2003, ErrorCode::USER_DISABLED->value);
    }

    public function test_product_error_codes_exist(): void
    {
        $this->assertSame(3000, ErrorCode::PRODUCT_NOT_FOUND->value);
        $this->assertSame(3001, ErrorCode::PRODUCT_OUT_OF_STOCK->value);
        $this->assertSame(3002, ErrorCode::PRODUCT_PRICE_CHANGED->value);
    }

    public function test_order_error_codes_exist(): void
    {
        $this->assertSame(4000, ErrorCode::ORDER_NOT_FOUND->value);
        $this->assertSame(4001, ErrorCode::ORDER_CREATE_FAILED->value);
        $this->assertSame(4002, ErrorCode::ORDER_STATUS_INVALID->value);
        $this->assertSame(4003, ErrorCode::ORDER_CANCEL_FAILED->value);
    }

    public function test_payment_error_codes_exist(): void
    {
        $this->assertSame(5000, ErrorCode::PAYMENT_FAILED->value);
        $this->assertSame(5001, ErrorCode::PAYMENT_TIMEOUT->value);
        $this->assertSame(5002, ErrorCode::PAYMENT_AMOUNT_MISMATCH->value);
        $this->assertSame(5003, ErrorCode::PAYMENT_REFUND_FAILED->value);
    }

    public function test_error_code_ranges_are_correct(): void
    {
        $authCases = [
            ErrorCode::AUTH_LOGIN_FAILED,
            ErrorCode::AUTH_TOKEN_EXPIRED,
            ErrorCode::AUTH_TOKEN_INVALID,
            ErrorCode::AUTH_LOGOUT_FAILED,
        ];
        foreach ($authCases as $case) {
            $this->assertGreaterThanOrEqual(1000, $case->value);
            $this->assertLessThan(1100, $case->value);
        }

        $userCases = [
            ErrorCode::USER_NOT_FOUND,
            ErrorCode::USER_ALREADY_EXISTS,
            ErrorCode::USER_PASSWORD_ERROR,
            ErrorCode::USER_DISABLED,
        ];
        foreach ($userCases as $case) {
            $this->assertGreaterThanOrEqual(2000, $case->value);
            $this->assertLessThan(2100, $case->value);
        }
    }

    public function test_http_status_code_mapping(): void
    {
        $this->assertSame(200, ErrorCode::SUCCESS->httpStatus());
        $this->assertSame(500, ErrorCode::SYSTEM_ERROR->httpStatus());
        $this->assertSame(400, ErrorCode::BAD_REQUEST->httpStatus());
        $this->assertSame(401, ErrorCode::UNAUTHORIZED->httpStatus());
        $this->assertSame(403, ErrorCode::FORBIDDEN->httpStatus());
        $this->assertSame(404, ErrorCode::NOT_FOUND->httpStatus());
        $this->assertSame(422, ErrorCode::VALIDATION_ERROR->httpStatus());
        $this->assertSame(429, ErrorCode::TOO_MANY_REQUESTS->httpStatus());
    }

    public function test_error_code_has_message(): void
    {
        $this->assertNotEmpty(ErrorCode::SUCCESS->message());
        $this->assertNotEmpty(ErrorCode::SYSTEM_ERROR->message());
        $this->assertNotEmpty(ErrorCode::AUTH_LOGIN_FAILED->message());
    }
}
