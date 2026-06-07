<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Refunds\MockRefundGateway;
use App\Domains\Payment\Refunds\RefundGatewayFactory;
use App\Domains\Payment\Refunds\WalletRefundGateway;
use InvalidArgumentException;
use Tests\TestCase;

class RefundGatewayFactoryTest extends TestCase
{
    public function test_creates_mock_gateway_for_original(): void
    {
        $gateway = RefundGatewayFactory::make('original');
        $this->assertInstanceOf(MockRefundGateway::class, $gateway);
        $this->assertSame('original', $gateway->getPath());
    }

    public function test_creates_wallet_gateway_for_wallet(): void
    {
        $gateway = RefundGatewayFactory::make('wallet');
        $this->assertInstanceOf(WalletRefundGateway::class, $gateway);
        $this->assertSame('wallet', $gateway->getPath());
    }

    public function test_throws_for_unsupported_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RefundGatewayFactory::make('unknown');
    }
}
