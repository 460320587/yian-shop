<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Gateways;

use App\Domains\Payment\Gateways\WechatPayGateway;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WechatPayGatewayTest extends TestCase
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
            'api_v3_key' => 'APIKEY123APIKEY123APIKEY123',
            'private_key' => $this->keyPair['private_key'],
            'public_key' => $this->keyPair['public_key'],
            'serial_no' => 'SERIAL123',
            'notify_url' => 'https://example.com/webhooks/wechat-pay',
        ]);
    }

    public function test_builds_qrcode_credential(): void
    {
        Http::fake([
            'api.mch.weixin.qq.com/*' => Http::response([
                'code_url' => 'weixin://wxpay/bizpayurl?pr=Test123',
            ], 200),
        ]);

        $gateway = new WechatPayGateway();
        $payment = Payment::factory()->create([
            'gateway' => 'wechat',
            'payment_no' => 'P202601010001',
            'amount' => 5000,
        ]);

        $credential = $gateway->buildCredential($payment);

        $this->assertEquals('qrcode', $credential['type']);
        $this->assertEquals('weixin://wxpay/bizpayurl?pr=Test123', $credential['qrcode_url']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mch.weixin.qq.com/v3/pay/transactions/native'
                && $request->hasHeader('Authorization');
        });
    }

    public function test_returns_name(): void
    {
        $gateway = new WechatPayGateway();
        $this->assertEquals('wechat', $gateway->getName());
    }

    public function test_throws_when_config_missing(): void
    {
        config()->set('payment.wechatpay', null);

        $gateway = new WechatPayGateway();
        $payment = Payment::factory()->create(['gateway' => 'wechat']);

        $this->expectException(\InvalidArgumentException::class);
        $gateway->buildCredential($payment);
    }
}
