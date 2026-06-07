<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Refunds;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\AlipayRefundGateway;
use App\Domains\Payment\Support\AlipaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlipayRefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = AlipaySigner::generateKeyPair();
        config()->set('payment.alipay', [
            'app_id' => 'APP456',
            'private_key' => $this->keyPair['private_key'],
        ]);
    }

    public function test_returns_correct_path(): void
    {
        $gateway = new AlipayRefundGateway();
        $this->assertSame('original', $gateway->getPath());
    }

    public function test_refunds_via_alipay_api(): void
    {
        Http::fake([
            'openapi.alipay.com/*' => Http::response([
                'alipay_trade_refund_response' => [
                    'code' => '10000',
                    'msg' => 'Success',
                    'refund_fee' => '50.00',
                    'out_trade_no' => 'P202601010001',
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
            'reason' => '质量问题',
            'refund_path' => 'original',
        ]);

        $gateway = new AlipayRefundGateway();
        $response = $gateway->refund($refund);

        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('gateway_refund_no', $response);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'openapi.alipay.com')
                && $request->method() === 'POST';
        });
    }

    public function test_throws_when_config_missing(): void
    {
        config()->set('payment.alipay', null);

        $payment = Payment::factory()->create(['gateway' => 'alipay']);
        $refund = RefundRecord::factory()->create([
            'payment_id' => $payment->id,
            'refund_path' => 'original',
        ]);

        $gateway = new AlipayRefundGateway();
        $this->expectException(\InvalidArgumentException::class);
        $gateway->refund($refund);
    }
}
