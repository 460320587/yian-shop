<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Support;

use App\Domains\Payment\Support\AlipaySigner;
use Tests\TestCase;

class AlipaySignerTest extends TestCase
{
    private array $keyPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyPair = AlipaySigner::generateKeyPair();
    }

    public function test_signs_sorted_params(): void
    {
        $signer = new AlipaySigner($this->keyPair['private_key']);
        $params = ['b_key' => '2', 'a_key' => '1', 'c_key' => '3'];

        $signature = $signer->sign($params);

        $this->assertNotEmpty($signature);
        $this->assertTrue($signer->verify($params, $signature, $this->keyPair['public_key']));
    }

    public function test_excludes_sign_and_empty_values(): void
    {
        $signer = new AlipaySigner($this->keyPair['private_key']);
        $params = ['a' => '1', 'sign' => 'xxx', 'b' => '', 'c' => '2'];

        $signature = $signer->sign($params);

        $this->assertTrue($signer->verify(['a' => '1', 'c' => '2'], $signature, $this->keyPair['public_key']));
    }

    public function test_rejects_invalid_signature(): void
    {
        $signer = new AlipaySigner($this->keyPair['private_key']);
        $params = ['a' => '1'];

        $signature = $signer->sign($params);

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

        $this->assertFalse($signer->verify($params, $signature, $wrongPublicKey));
    }
}
