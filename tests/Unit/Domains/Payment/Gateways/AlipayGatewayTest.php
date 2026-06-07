<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Gateways;

use App\Domains\Payment\Gateways\AlipayGateway;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Support\AlipaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlipayGatewayTest extends TestCase
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
            'public_key' => $this->keyPair['public_key'],
            'alipay_public_key' => $this->keyPair['public_key'],
            'notify_url' => 'https://example.com/webhooks/alipay',
        ]);
    }

    public function test_builds_qrcode_credential(): void
    {
        Http::fake([
            'openapi.alipay.com/*' => Http::response([
                'alipay_trade_precreate_response' => [
                    'code' => '10000',
                    'msg' => 'Success',
                    'qr_code' => 'https://qr.alipay.com/Test456',
                    'out_trade_no' => 'P202601010001',
                ],
                'sign' => 'mock-sign',
            ], 200),
        ]);

        $gateway = new AlipayGateway();
        $payment = Payment::factory()->create([
            'gateway' => 'alipay',
            'payment_no' => 'P202601010001',
            'amount' => 5000,
        ]);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('qrcode', $credential['type']);
        $this->assertEquals('https://qr.alipay.com/Test456', $credential['qrcode_url']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'openapi.alipay.com')
                && $request->method() === 'POST';
        });
    }

    public function test_returns_name(): void
    {
        $gateway = new AlipayGateway();
        $this->assertEquals('alipay', $gateway->getName());
    }

    public function test_throws_when_config_missing(): void
    {
        config()->set('payment.alipay', null);

        $gateway = new AlipayGateway();
        $payment = Payment::factory()->create(['gateway' => 'alipay']);

        $this->expectException(\InvalidArgumentException::class);
        $gateway->buildCredential($payment);
    }
}
