<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Webhooks;

use App\Domains\Payment\Webhooks\AlipayWebhookVerifier;
use App\Domains\Payment\Webhooks\MockWebhookVerifier;
use App\Domains\Payment\Webhooks\WebhookVerifierFactory;
use App\Domains\Payment\Webhooks\WechatPayWebhookVerifier;
use InvalidArgumentException;
use Tests\TestCase;

class WebhookVerifierFactoryTest extends TestCase
{
    public function test_creates_mock_verifier_by_default(): void
    {
        config()->set('payment.use_real_gateway', false);
        config()->set('payment.webhook_verify_mode', 'skip');

        $verifier = WebhookVerifierFactory::make('wechat');
        $this->assertInstanceOf(MockWebhookVerifier::class, $verifier);
        $this->assertSame('wechat', $verifier->getGateway());
    }

    public function test_creates_real_verifier_when_configured(): void
    {
        config()->set('payment.use_real_gateway', true);

        $wechat = WebhookVerifierFactory::make('wechat');
        $this->assertInstanceOf(WechatPayWebhookVerifier::class, $wechat);

        $alipay = WebhookVerifierFactory::make('alipay');
        $this->assertInstanceOf(AlipayWebhookVerifier::class, $alipay);
    }

    public function test_throws_for_unsupported_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WebhookVerifierFactory::make('unknown');
    }
}
