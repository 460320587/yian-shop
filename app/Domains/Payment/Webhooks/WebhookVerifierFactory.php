<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use InvalidArgumentException;

/**
 * Webhook 签名验证器工厂
 *
 * 根据网关名称创建对应的验证器实例。
 * 当前所有网关均返回 MockWebhookVerifier（开发环境占位）。
 */
class WebhookVerifierFactory
{
    private static array $supportedGateways = ['wechat', 'alipay', 'unionpay'];

    public static function make(string $gateway): WebhookSignatureVerifierInterface
    {
        if (! in_array($gateway, self::$supportedGateways, true)) {
            throw new InvalidArgumentException("Unsupported webhook gateway: {$gateway}");
        }

        $mode = config('payment.webhook_verify_mode', 'skip');

        return new MockWebhookVerifier($gateway, $mode);
    }
}
