<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use App\Domains\Payment\Support\AlipaySigner;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * 支付宝回调签名验证器
 *
 * 验证支付宝异步通知的签名。
 */
class AlipayWebhookVerifier implements WebhookSignatureVerifierInterface
{
    public function getGateway(): string
    {
        return 'alipay';
    }

    public function verify(Request $request): bool
    {
        $publicKey = config('payment.alipay.alipay_public_key');
        if (empty($publicKey)) {
            throw new InvalidArgumentException('Alipay public key not configured');
        }

        $params = $request->all();
        $signature = $params['sign'] ?? '';

        if (empty($signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Missing Alipay signature'],
            ]);
        }

        if (! AlipaySigner::verifyWithPublicKey($params, $signature, $publicKey)) {
            throw ValidationException::withMessages([
                'signature' => ['Alipay webhook signature verification failed'],
            ]);
        }

        return true;
    }
}
