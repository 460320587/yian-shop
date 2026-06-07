<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use InvalidArgumentException;

/**
 * 原路退回退款网关（路由层）
 *
 * 根据关联支付的网关类型，自动分发到微信或支付宝退款网关。
 */
class OriginalRefundGateway implements RefundGatewayInterface
{
    public function getPath(): string
    {
        return 'original';
    }

    public function refund(RefundRecord $refund): array
    {
        $payment = $refund->payment;
        if (! $payment) {
            throw new InvalidArgumentException('Refund missing associated payment');
        }

        $gateway = match ($payment->gateway) {
            'wechat' => new WechatPayRefundGateway(),
            'alipay' => new AlipayRefundGateway(),
            default => throw new InvalidArgumentException("Unsupported original payment gateway: {$payment->gateway}"),
        };

        return $gateway->refund($refund);
    }
}
