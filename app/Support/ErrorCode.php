<?php

declare(strict_types=1);

namespace App\Support;

enum ErrorCode: int
{
    // 通用错误码 0-999
    case SUCCESS = 0;
    case SYSTEM_ERROR = 500;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case VALIDATION_ERROR = 422;
    case TOO_MANY_REQUESTS = 429;

    // 认证模块 1000-1099
    case AUTH_LOGIN_FAILED = 1000;
    case AUTH_TOKEN_EXPIRED = 1001;
    case AUTH_TOKEN_INVALID = 1002;
    case AUTH_LOGOUT_FAILED = 1003;

    // 用户模块 2000-2099
    case USER_NOT_FOUND = 2000;
    case USER_ALREADY_EXISTS = 2001;
    case USER_PASSWORD_ERROR = 2002;
    case USER_DISABLED = 2003;

    // 商品模块 3000-3099
    case PRODUCT_NOT_FOUND = 3000;
    case PRODUCT_OUT_OF_STOCK = 3001;
    case PRODUCT_PRICE_CHANGED = 3002;

    // 购物车模块 3100-3199
    case CART_EMPTY = 3100;

    // 订单模块 4000-4099
    case ORDER_NOT_FOUND = 4000;
    case ORDER_CREATE_FAILED = 4001;
    case ORDER_STATUS_INVALID = 4002;
    case ORDER_CANCEL_FAILED = 4003;

    // 支付模块 5000-5099
    case PAYMENT_FAILED = 5000;
    case PAYMENT_TIMEOUT = 5001;
    case PAYMENT_AMOUNT_MISMATCH = 5002;
    case PAYMENT_REFUND_FAILED = 5003;

    public function message(): string
    {
        return match ($this) {
            self::SUCCESS => '成功',
            self::SYSTEM_ERROR => '系统错误',
            self::BAD_REQUEST => '请求参数错误',
            self::UNAUTHORIZED => '未授权',
            self::FORBIDDEN => '禁止访问',
            self::NOT_FOUND => '资源不存在',
            self::VALIDATION_ERROR => '数据验证失败',
            self::TOO_MANY_REQUESTS => '请求过于频繁',
            self::AUTH_LOGIN_FAILED => '登录失败',
            self::AUTH_TOKEN_EXPIRED => 'Token已过期',
            self::AUTH_TOKEN_INVALID => 'Token无效',
            self::AUTH_LOGOUT_FAILED => '退出登录失败',
            self::USER_NOT_FOUND => '用户不存在',
            self::USER_ALREADY_EXISTS => '用户已存在',
            self::USER_PASSWORD_ERROR => '密码错误',
            self::USER_DISABLED => '用户已被禁用',
            self::PRODUCT_NOT_FOUND => '商品不存在',
            self::PRODUCT_OUT_OF_STOCK => '商品库存不足',
            self::PRODUCT_PRICE_CHANGED => '商品价格已变动',
            self::CART_EMPTY => '购物车为空',
            self::ORDER_NOT_FOUND => '订单不存在',
            self::ORDER_CREATE_FAILED => '订单创建失败',
            self::ORDER_STATUS_INVALID => '订单状态异常',
            self::ORDER_CANCEL_FAILED => '订单取消失败',
            self::PAYMENT_FAILED => '支付失败',
            self::PAYMENT_TIMEOUT => '支付超时',
            self::PAYMENT_AMOUNT_MISMATCH => '支付金额不匹配',
            self::PAYMENT_REFUND_FAILED => '退款失败',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::SUCCESS => 200,
            self::BAD_REQUEST => 400,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::NOT_FOUND => 404,
            self::PRODUCT_NOT_FOUND => 404,
            self::ORDER_NOT_FOUND => 404,
            self::VALIDATION_ERROR => 422,
            self::TOO_MANY_REQUESTS => 429,
            self::SYSTEM_ERROR => 500,
            default => 200,
        };
    }
}
