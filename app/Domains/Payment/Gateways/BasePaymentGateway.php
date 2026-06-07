<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Models\Payment;

/**
 * 支付网关抽象基类
 *
 * 提供通用工具方法，具体网关只需实现 buildCredential。
 */
abstract class BasePaymentGateway implements PaymentGatewayInterface
{
    /**
     * 生成标准格式的二维码 URL（开发环境 Mock 用）
     */
    protected function mockQrcodeUrl(string $prefix, Payment $payment): string
    {
        return $prefix . $payment->payment_no;
    }
}
