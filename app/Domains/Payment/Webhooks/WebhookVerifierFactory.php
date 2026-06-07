<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use InvalidArgumentException;

/**
 * Webhook 签名验证器工厂
 *
 * 根据网关名称和配置创建对应的验证器实例。
 * 当 payment.use_real_gateway 为 true 时返回真实验签器，否则返回 Mock。
 */
class WebhookVerifierFactory
{
    private static array $supportedGateways = ['wechat', 'alipay', 'unionpay'];

    public static function make(string $gateway): WebhookSignatureVerifierInterface
    {
        if (! in_array($gateway, self::$supportedGateways, true)) {
            throw new InvalidArgumentException("Unsupported webhook gateway: {$gateway}");
        }

        $useReal = config('payment.use_real_gateway', false);

        if (! $useReal) {
            $mode = config('payment.webhook_verify_mode', 'skip');
            return new MockWebhookVerifier($gateway, $mode);
        }

        return match ($gateway) {
            'wechat' => new WechatPayWebhookVerifier(),
            'alipay' => new AlipayWebhookVerifier(),
            default => new MockWebhookVerifier($gateway, 'strict'),
        };
    }
}
