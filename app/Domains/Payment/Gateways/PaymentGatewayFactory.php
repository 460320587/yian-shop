<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

/**
 * 支付网关工厂
 *
 * 根据网关名称创建对应的网关实例。
 * 当前所有非 wallet 网关均返回 MockPaymentGateway（开发环境占位）。
 */
class PaymentGatewayFactory
{
    /**
     * 创建网关实例
     */
    public static function make(string $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            'wechat', 'alipay', 'unionpay' => new MockPaymentGateway(),
            default => new MockPaymentGateway(),
        };
    }
}
