<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Webhooks;

use App\Domains\Payment\Support\WechatPaySigner;
use App\Domains\Payment\Webhooks\WechatPayWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WechatPayWebhookVerifierTest extends TestCase
{
    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = WechatPaySigner::generateKeyPair();
        config()->set('payment.wechatpay.public_key', $this->keyPair['public_key']);
    }

    public function test_verifies_valid_signature(): void
    {
        $body = '{"callback":true}';
        $timestamp = '1620000000';
        $nonce = 'nonce123';

        $signer = new WechatPaySigner($this->keyPair['private_key']);
        $signature = $signer->sign("$timestamp\n$nonce\n$body");

        $request = Request::create('/webhooks/wechat-pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Wechatpay_Timestamp' => $timestamp,
            'HTTP_Wechatpay_Nonce' => $nonce,
            'HTTP_Wechatpay_Signature' => $signature,
            'HTTP_Wechatpay_Serial' => 'serial123',
        ], $body);

        $verifier = new WechatPayWebhookVerifier();
        $this->assertTrue($verifier->verify($request));
    }

    public function test_rejects_invalid_signature(): void
    {
        $body = '{"callback":true}';
        $wrongKeyPair = WechatPaySigner::generateKeyPair();

        $request = Request::create('/webhooks/wechat-pay', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Wechatpay_Timestamp' => '1620000000',
            'HTTP_Wechatpay_Nonce' => 'nonce123',
            'HTTP_Wechatpay_Signature' => base64_encode('invalid'),
            'HTTP_Wechatpay_Serial' => 'serial123',
        ], $body);

        config()->set('payment.wechatpay.public_key', $wrongKeyPair['public_key']);

        $verifier = new WechatPayWebhookVerifier();
        $this->expectException(ValidationException::class);
        $verifier->verify($request);
    }

    public function test_returns_gateway_name(): void
    {
        $verifier = new WechatPayWebhookVerifier();
        $this->assertEquals('wechat', $verifier->getGateway());
    }
}
