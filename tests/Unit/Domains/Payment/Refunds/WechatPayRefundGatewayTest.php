<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\WechatPayRefundGateway;
use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WechatPayRefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = WechatPaySigner::generateKeyPair();
        config()->set('payment.wechatpay', [
            'mch_id' => 'MCH123',
            'app_id' => 'APP123',
            'private_key' => $this->keyPair['private_key'],
            'serial_no' => 'SERIAL123',
        ]);
    }

    public function test_returns_correct_path(): void
    {
        $gateway = new WechatPayRefundGateway();
        $this->assertSame('original', $gateway->getPath());
    }

    public function test_refunds_via_wechat_api(): void
    {
        Http::fake([
            'api.mch.weixin.qq.com/*' => Http::response([
                'refund_id' => 'WX_REFUND_123',
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
            'reason' => '质量问题',
            'refund_path' => 'original',
        ]);

        $gateway = new WechatPayRefundGateway();
        $response = $gateway->refund($refund);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('WX_REFUND_123', $response['gateway_refund_no']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds'
                && $request->hasHeader('Authorization');
        });
    }

    public function test_throws_when_config_missing(): void
    {
        config()->set('payment.wechatpay', null);

        $payment = Payment::factory()->create(['gateway' => 'wechat']);
        $refund = RefundRecord::factory()->create([
            'payment_id' => $payment->id,
            'refund_path' => 'original',
        ]);

        $gateway = new WechatPayRefundGateway();
        $this->expectException(\InvalidArgumentException::class);
        $gateway->refund($refund);
    }
}
