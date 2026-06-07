<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Gateways;

use App\Domains\Payment\Gateways\MockPaymentGateway;
use App\Domains\Payment\Gateways\PaymentGatewayFactory;
use Tests\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    public function test_returns_mock_gateway_for_wechat(): void
    {
        $gateway = PaymentGatewayFactory::make('wechat');

        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }

    public function test_returns_mock_gateway_for_alipay(): void
    {
        $gateway = PaymentGatewayFactory::make('alipay');

        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }

    public function test_returns_mock_gateway_for_unionpay(): void
    {
        $gateway = PaymentGatewayFactory::make('unionpay');

        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }

    public function test_returns_mock_gateway_for_unknown_gateway(): void
    {
        $gateway = PaymentGatewayFactory::make('unknown');

        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }
}
