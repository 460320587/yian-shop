<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Gateways;

use App\Domains\Payment\Gateways\MockPaymentGateway;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_wechat_credential(): void
    {
        $gateway = new MockPaymentGateway();
        $payment = Payment::factory()->create(['gateway' => 'wechat', 'payment_no' => 'P20260101000001']);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('qrcode', $credential['type']);
        $this->assertStringContainsString('weixin://wxpay/mock/', $credential['qrcode_url']);
        $this->assertStringContainsString('P20260101000001', $credential['qrcode_url']);
    }

    public function test_builds_alipay_credential(): void
    {
        $gateway = new MockPaymentGateway();
        $payment = Payment::factory()->create(['gateway' => 'alipay', 'payment_no' => 'P20260101000002']);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('qrcode', $credential['type']);
        $this->assertStringContainsString('https://qr.alipay.com/mock/', $credential['qrcode_url']);
        $this->assertStringContainsString('P20260101000002', $credential['qrcode_url']);
    }

    public function test_builds_unionpay_credential(): void
    {
        $gateway = new MockPaymentGateway();
        $payment = Payment::factory()->create(['gateway' => 'unionpay', 'payment_no' => 'P20260101000003']);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('redirect', $credential['type']);
        $this->assertStringContainsString('https://unionpay.com/mock/', $credential['redirect_url']);
        $this->assertStringContainsString('P20260101000003', $credential['redirect_url']);
    }

    public function test_builds_default_credential_for_unknown_gateway(): void
    {
        $gateway = new MockPaymentGateway();
        $payment = Payment::factory()->create(['gateway' => 'bitcoin', 'payment_no' => 'P20260101000004']);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('qrcode', $credential['type']);
        $this->assertStringContainsString('https://mock.qrcode/', $credential['qrcode_url']);
        $this->assertStringContainsString('P20260101000004', $credential['qrcode_url']);
    }

    public function test_returns_name(): void
    {
        $gateway = new MockPaymentGateway();

        $this->assertEquals('mock', $gateway->getName());
    }
}
