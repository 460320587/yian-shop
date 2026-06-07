<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Webhooks;

use App\Domains\Payment\Webhooks\MockWebhookVerifier;
use App\Domains\Payment\Webhooks\WebhookVerifierFactory;
use InvalidArgumentException;
use Tests\TestCase;

class WebhookVerifierFactoryTest extends TestCase
{
    public function test_creates_mock_verifier_for_wechat(): void
    {
        $verifier = WebhookVerifierFactory::make('wechat');
        $this->assertInstanceOf(MockWebhookVerifier::class, $verifier);
        $this->assertSame('wechat', $verifier->getGateway());
    }

    public function test_creates_mock_verifier_for_alipay(): void
    {
        $verifier = WebhookVerifierFactory::make('alipay');
        $this->assertInstanceOf(MockWebhookVerifier::class, $verifier);
        $this->assertSame('alipay', $verifier->getGateway());
    }

    public function test_creates_mock_verifier_for_unionpay(): void
    {
        $verifier = WebhookVerifierFactory::make('unionpay');
        $this->assertInstanceOf(MockWebhookVerifier::class, $verifier);
        $this->assertSame('unionpay', $verifier->getGateway());
    }

    public function test_throws_for_unsupported_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        WebhookVerifierFactory::make('unknown');
    }
}
