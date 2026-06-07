<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\AlipayRefundGateway;
use App\Domains\Payment\Refunds\MockRefundGateway;
use App\Domains\Payment\Refunds\OriginalRefundGateway;
use App\Domains\Payment\Refunds\WechatPayRefundGateway;
use App\Domains\Payment\Support\AlipaySigner;
use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OriginalRefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $keyPair = WechatPaySigner::generateKeyPair();
        config()->set('payment.wechatpay', [
            'mch_id' => 'MCH123',
            'app_id' => 'APP123',
            'private_key' => $keyPair['private_key'],
            'serial_no' => 'SERIAL123',
        ]);
        $alipayKeyPair = AlipaySigner::generateKeyPair();
        config()->set('payment.alipay', [
            'app_id' => 'APP456',
            'private_key' => $alipayKeyPair['private_key'],
        ]);
    }

    public function test_returns_correct_path(): void
    {
        $gateway = new OriginalRefundGateway();
        $this->assertSame('original', $gateway->getPath());
    }

    public function test_routes_to_wechat_gateway(): void
    {
        Http::fake([
            'api.mch.weixin.qq.com/*' => Http::response([
                'refund_id' => 'WX_REFUND_001',
                'status' => 'SUCCESS',
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'gateway' => 'wechat',
            'payment_no' => 'P202601010001',
            'amount' => 10000,
        ]);
        $refund = RefundRecord::factory()->create([
            'payment_id' => $payment->id,
            'refund_no' => 'R202601010001',
            'amount' => 5000,
            'refund_path' => 'original',
        ]);

        $gateway = new OriginalRefundGateway();
        $response = $gateway->refund($refund);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('WX_REFUND_001', $response['gateway_refund_no']);
    }

    public function test_routes_to_alipay_gateway(): void
    {
        Http::fake([
            'openapi.alipay.com/*' => Http::response([
                'alipay_trade_refund_response' => [
                    'code' => '10000',
                    'msg' => 'Success',
                    'refund_fee' => '50.00',
                ],
                'sign' => 'mock-sign',
            ], 200),
        ]);

        $payment = Payment::factory()->create([
            'gateway' => 'alipay',
            'payment_no' => 'P202601010001',
            'amount' => 10000,
        ]);
        $refund = RefundRecord::factory()->create([
            'payment_id' => $payment->id,
            'refund_no' => 'R202601010001',
            'amount' => 5000,
            'refund_path' => 'original',
        ]);

        $gateway = new OriginalRefundGateway();
        $response = $gateway->refund($refund);

        $this->assertEquals('success', $response['status']);
    }

    public function test_throws_for_unsupported_payment_gateway(): void
    {
        $payment = Payment::factory()->create([
            'gateway' => 'unionpay',
            'payment_no' => 'P202601010001',
        ]);
        $refund = RefundRecord::factory()->create([
            'payment_id' => $payment->id,
            'refund_path' => 'original',
        ]);

        $gateway = new OriginalRefundGateway();
        $this->expectException(\InvalidArgumentException::class);
        $gateway->refund($refund);
    }

    public function test_throws_when_payment_missing(): void
    {
        $refund = new RefundRecord();
        $refund->refund_no = 'R_TEST_001';
        $refund->refund_path = 'original';

        $gateway = new OriginalRefundGateway();
        $this->expectException(\InvalidArgumentException::class);
        $gateway->refund($refund);
    }
}
