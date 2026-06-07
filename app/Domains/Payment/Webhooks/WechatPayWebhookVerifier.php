<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 微信支付回调签名验证器
 *
 * 验证微信 V3 回调请求的签名。
 */
class WechatPayWebhookVerifier implements WebhookSignatureVerifierInterface
{
    public function getGateway(): string
    {
        return 'wechat';
    }

    public function verify(Request $request): bool
    {
        $publicKey = config('payment.wechatpay.public_key');
        if (empty($publicKey)) {
            throw new InvalidArgumentException('WeChat Pay public key not configured');
        }

        $timestamp = $request->header('Wechatpay-Timestamp', '');
        $nonce = $request->header('Wechatpay-Nonce', '');
        $signature = $request->header('Wechatpay-Signature', '');

        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Missing WeChat Pay webhook headers'],
            ]);
        }

        $body = $request->getContent();
        $message = "$timestamp\n$nonce\n$body";

        if (! WechatPaySigner::verifyWithPublicKey($message, $signature, $publicKey)) {
            throw ValidationException::withMessages([
                'signature' => ['WeChat Pay webhook signature verification failed'],
            ]);
        }

        return true;
    }
}
