<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Support;

use App\Domains\Payment\Support\WechatPaySigner;
use Tests\TestCase;

class WechatPaySignerTest extends TestCase
{
    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = WechatPaySigner::generateKeyPair();
    }

    public function test_signs_request_string(): void
    {
        $signer = new WechatPaySigner($this->keyPair['private_key']);
        $message = "POST\n/v3/pay/transactions/native\n1620000000\nnonce123\n{\"body\":true}\n";

        $signature = $signer->sign($message);

        $this->assertNotEmpty($signature);
        $this->assertTrue($signer->verify($message, $signature, $this->keyPair['public_key']));
    }

    public function test_verifies_callback_signature(): void
    {
        $signer = new WechatPaySigner($this->keyPair['private_key']);
        $message = "1620000000\nnonce456\n{\"callback\":true}";

        $signature = $signer->sign($message);

        $this->assertTrue($signer->verify($message, $signature, $this->keyPair['public_key']));
    }

    public function test_rejects_invalid_signature(): void
    {
        $signer = new WechatPaySigner($this->keyPair['private_key']);
        $message = "test-message";

        $signature = $signer->sign($message);

        // 使用一个不同的公钥（预生成）
        $wrongPublicKey = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAw18wCSRBd//6XAINbu/7
6eox9OJ6XPpVCLd9FhLIrxaxATnu5On9DmD9GKmSncKVLdmL9E4Yl9fUkectydTQ
7K0m+kI4Q/0PHHOvGemtHD47MYoXeZmkza0dWQBtuZVm9p3t6BB3hWSjoaeTpZhg
i/1lPHkiHuSbONpsSPSmY8SXJQf43yf9myJdzStqWizXbxOaMWI1aOSy6eiRjtfm
244wEN74UJ+7dk8VRW4tf6A39dH9DZ92UsLdtjBC0PbV28qDWb2McySLp1yoswkv
+nRrhjrO1ne3w0eSKlplFrsiBn6kXXhq/E1S9mB0VJQTre9KrJ1/EoUyr3vN14Qq
zQIDAQAB
-----END PUBLIC KEY-----
PEM;

        $this->assertFalse($signer->verify($message, $signature, $wrongPublicKey));
    }

    public function test_generates_serial_number(): void
    {
        $serial = WechatPaySigner::getSerialNumber($this->keyPair['public_key']);

        $this->assertNotEmpty($serial);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $serial);
    }

    public function test_builds_authorization_header(): void
    {
        $signer = new WechatPaySigner($this->keyPair['private_key']);
        $header = $signer->buildAuthorizationHeader('POST', '/v3/pay/transactions/native', '{"test":true}', 'MCH123', 'serial-no-123');

        $this->assertStringStartsWith('WECHATPAY2-SHA256-RSA2048', $header);
        $this->assertStringContainsString('mchid="MCH123"', $header);
        $this->assertStringContainsString('serial_no="serial-no-123"', $header);
    }
}
