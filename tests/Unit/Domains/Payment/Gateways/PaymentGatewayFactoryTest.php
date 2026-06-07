<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Gateways;

use App\Domains\Payment\Gateways\AlipayGateway;
use App\Domains\Payment\Gateways\MockPaymentGateway;
use App\Domains\Payment\Gateways\PaymentGatewayFactory;
use App\Domains\Payment\Gateways\WechatPayGateway;
use Tests\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    public function test_returns_mock_gateway_by_default(): void
    {
        config()->set('payment.use_real_gateway', false);

        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('wechat'));
        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('alipay'));
        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('unionpay'));
        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('unknown'));
    }

    public function test_returns_real_gateway_when_configured(): void
    {
        config()->set('payment.use_real_gateway', true);

        $this->assertInstanceOf(WechatPayGateway::class, PaymentGatewayFactory::make('wechat'));
        $this->assertInstanceOf(AlipayGateway::class, PaymentGatewayFactory::make('alipay'));
        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('unionpay'));
        $this->assertInstanceOf(MockPaymentGateway::class, PaymentGatewayFactory::make('unknown'));
    }
}
