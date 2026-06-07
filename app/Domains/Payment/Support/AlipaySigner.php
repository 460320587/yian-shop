<?php

declare(strict_types=1);

namespace App\Domains\Payment\Support;

use InvalidArgumentException;
use OpenSSLAsymmetricKey;

/**
 * 支付宝 RSA2 签名工具
 *
 * 支持请求签名生成和回调签名验证。
 */
class AlipaySigner
{
    private OpenSSLAsymmetricKey $privateKey;

    public function __construct(string $privateKeyPem)
    {
        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false) {
            throw new InvalidArgumentException('Invalid Alipay private key');
        }
        $this->privateKey = $key;
    }

    /**
     * 对参数进行 RSA2(SHA256) 签名
     */
    public function sign(array $params): string
    {
        $string = $this->buildSignString($params);
        $signature = '';
        openssl_sign($string, $signature, $this->privateKey, 'sha256WithRSAEncryption');
        return base64_encode($signature);
    }

    /**
     * 使用公钥验证签名（实例方法）
     */
    public function verify(array $params, string $signature, string $publicKeyPem): bool
    {
        return self::verifyWithPublicKey($params, $signature, $publicKeyPem);
    }

    /**
     * 使用公钥验证签名（静态方法，无需构造实例）
     */
    public static function verifyWithPublicKey(array $params, string $signature, string $publicKeyPem): bool
    {
        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) {
            return false;
        }
        $string = self::buildSignString($params);
        return openssl_verify($string, base64_decode($signature), $key, 'sha256WithRSAEncryption') === 1;
    }

    /**
     * 构建待签名字符串（排序、排除 sign/sign_type/空值）
     */
    public static function buildSignString(array $params): string
    {
        // 排除 sign、sign_type 和空值
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $k === 'sign_type' || $v === '' || $v === null) {
                continue;
            }
            $filtered[$k] = $v;
        }
        ksort($filtered);

        $pairs = [];
        foreach ($filtered as $k => $v) {
            $pairs[] = $k . '=' . $v;
        }
        return implode('&', $pairs);
    }

    /**
     * 生成 RSA 密钥对（测试用）
     *
     * @return array{private_key: string, public_key: string}
     */
    public static function generateKeyPair(): array
    {
        // 复用同一对预生成 2048-bit RSA 密钥（避免运行环境 OpenSSL 配置缺失问题）
        $privateKey = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCZcPs2yDVtHNTS
77f9vnDd+t4geYVEhURYNueJdhXkzQYFiRWE+2XBNPxEdghu/h9XhZQyExUs0GOH
5kgLSZc+AfaKEG5Tg5gdlqs9Qvd3a+Hi0FwSmM4Mp3eZRGTiAroJbSXlReuUyXNy
g5Ahj6Bc091W14Y41YLDOeHvJ5pqPyfnvGCPm8maTtQgRe0tVp04gjH7inOuQfi8
yVpuSqSoqiZhvkm5knjLIt8KvWTm7ZTmegJDm/JGIw3opk9poc+fAykl3ua7tJKZ
LaPRq2JUDrk8b6W4AKf+XckADVCKvCeaqoay37og1BsVjmUzXAAC8tYpwkX1QkRb
EIzs/iinAgMBAAECggEAO/x8aYVJxbdwyGopHw0VqOKLdlyrqgcDmg0U5ZuBoao5
gW5/ODbkJ+1j9gvC7klwzVGOhykIrTVmw9lWLvt9IiO4U29rAkE+9cpSdae5gTlu
3LIiXK7aVv4ddpDTc7wnKMo/92snV0qOVKV0cnpo8gFt1aZ6dbua3GHDnzB3YLNn
GbuGVgiKJTJ0wNupX9ngCbXJE+hRICRhSFIgq7xUGGGXRwZy3VyJiCy81AJ6LNND
IFbsrS9I2C98OSexrMRJ6c1bsrZ5jaTJLHZNuClmv1XsefDsUi8Mtq6jhYZ66W69
clA9qFaXTSHp+3z0ATrkDb99TazULhea/pTVfsGn6QKBgQDG8eMZ3rk8I55ppXhv
6IuXSo7pDV1r5xEIEPABhBloZnwUm5u72sDNv/cJ9AloDrF//ekajxezD3CrLaWd
VGfSbAqCWSzddLWSSuBdkR/+T/Y5SeQRGwwYI36fFF1LKw1JLP5SObrSf5dl55Hy
sxukYYOQaFtNuDp7Oj5wI2mniwKBgQDFclIEMy3os5AsQfH3ksGxIhls+97ndcNO
SZhzXU7So1folONbbHV0cPmagl8nyVKS3n5o/O112Bni/SY3cFy0Kwj7sHJn4OgN
RdLz9HnGqu6gQyUm5Fzv9XTWxGjnq0O6W4K9QVZjR/prxA2899MI5u6lwU0NaW0X
eNmqXYmG1QKBgG7SMZqomfDpOoZJObTFCnVlgARMgJzp42/l13xQtMKWTEpCgFb9
vWcfpyJxQYgonzHYJDC6Qw5o7G8+h0ID0a3Cp3wC5t4Z9Ecg1KjqfbLxogw5oATz
VyJfK1q8QlfsDIOVv7nYFLzLfG+0tnVG1oAUHfCkYTADDviz8jIPV/yPAoGAXW80
2qHxdq4KarcmLtb268DMMWObXwK+UnzHNMiR9WGwxvI2CNxxVJHlvDU3nFdLyQ6a
2UnfgSWrUlwjSpbUd7npvVkJOpkWlrlVE3a8bcQTuiksGpR0WmVYTg2R6xeDrFuD
qdWScNbt7TNQoPzRk+PcSPoJYXBjXHkm39T1sKUCgYAe2qQ2KGzKOqm5O/j4UQzr
RHIJiTJPsOnBFR4oq9OxgLPn1V4hkDWT5CYD+Zyc8dAjt8xf1u5pl3Jd78cVmbPp
4x8lRDkwYnM3SWHSnGZogJy0sBMK1keQfzO/CkLHT/lI2kFLmbEyzjm261V7hWQt
IPBi1uPhFpWLqqAk1sE+Dg==
-----END PRIVATE KEY-----
PEM;

        $publicKey = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmXD7Nsg1bRzU0u+3/b5w
3freIHmFRIVEWDbniXYV5M0GBYkVhPtlwTT8RHYIbv4fV4WUMhMVLNBjh+ZIC0mX
PgH2ihBuU4OYHZarPUL3d2vh4tBcEpjODKd3mURk4gK6CW0l5UXrlMlzcoOQIY+g
XNPdVteGONWCwznh7yeaaj8n57xgj5vJmk7UIEXtLVadOIIx+4pzrkH4vMlabkqk
qKomYb5JuZJ4yyLfCr1k5u2U5noCQ5vyRiMN6KZPaaHPnwMpJd7mu7SSmS2j0ati
VA65PG+luACn/l3JAA1QirwnmqqGst+6INQbFY5lM1wAAvLWKcJF9UJEWxCM7P4o
pwIDAQAB
-----END PUBLIC KEY-----
PEM;

        return [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
        ];
    }
}
