<?php

declare(strict_types=1);

namespace App\Domains\Payment\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Mock Webhook 签名验证器
 *
 * 开发环境使用。支持两种模式：
 * - skip（默认）：总是通过，方便开发调试
 * - strict：要求请求头包含 X-Mock-Signature: test，模拟真实验签流程
 */
class MockWebhookVerifier implements WebhookSignatureVerifierInterface
{
    public function __construct(
        private string $gateway,
        private string $mode = 'skip',
    ) {
    }

    public function getGateway(): string
    {
        return $this->gateway;
    }

    public function verify(Request $request): bool
    {
        if ($this->mode === 'skip') {
            return true;
        }

        $signature = $request->header('X-Mock-Signature');

        if ($signature !== 'test') {
            throw ValidationException::withMessages([
                'signature' => ['Mock webhook signature verification failed'],
            ]);
        }

        return true;
    }
}
