<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

/**
 * 支付网关工厂
 *
 * 根据网关名称和配置创建对应的网关实例。
 * 当 payment.use_real_gateway 为 true 时返回真实网关，否则返回 Mock。
 */
class PaymentGatewayFactory
{
    public static function make(string $gateway): PaymentGatewayInterface
    {
        $useReal = config('payment.use_real_gateway', false);

        if (! $useReal) {
            return new MockPaymentGateway();
        }

        return match ($gateway) {
            'wechat' => new WechatPayGateway(),
            'alipay' => new AlipayGateway(),
            default => new MockPaymentGateway(),
        };
    }
}
