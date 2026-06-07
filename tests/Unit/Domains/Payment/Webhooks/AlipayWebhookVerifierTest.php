<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Webhooks;

use App\Domains\Payment\Support\AlipaySigner;
use App\Domains\Payment\Webhooks\AlipayWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AlipayWebhookVerifierTest extends TestCase
{
    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = AlipaySigner::generateKeyPair();
        config()->set('payment.alipay.alipay_public_key', $this->keyPair['public_key']);
    }

    public function test_verifies_valid_signature(): void
    {
        $params = ['out_trade_no' => 'P001', 'trade_status' => 'TRADE_SUCCESS'];

        $signer = new AlipaySigner($this->keyPair['private_key']);
        $signature = $signer->sign($params);

        $request = Request::create('/webhooks/alipay', 'POST', array_merge($params, ['sign' => $signature, 'sign_type' => 'RSA2']));

        $verifier = new AlipayWebhookVerifier();
        $this->assertTrue($verifier->verify($request));
    }

    public function test_rejects_invalid_signature(): void
    {
        $wrongKeyPair = AlipaySigner::generateKeyPair();
        $params = ['out_trade_no' => 'P001', 'trade_status' => 'TRADE_SUCCESS'];

        $request = Request::create('/webhooks/alipay', 'POST', array_merge($params, ['sign' => base64_encode('invalid'), 'sign_type' => 'RSA2']));

        config()->set('payment.alipay.alipay_public_key', $wrongKeyPair['public_key']);

        $verifier = new AlipayWebhookVerifier();
        $this->expectException(ValidationException::class);
        $verifier->verify($request);
    }

    public function test_returns_gateway_name(): void
    {
        $verifier = new AlipayWebhookVerifier();
        $this->assertEquals('alipay', $verifier->getGateway());
    }
}
