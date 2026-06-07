<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Models\Payment;

/**
 * Mock 支付网关
 *
 * 开发环境使用的模拟网关，生成假二维码/跳转链接。
 * 真实微信/支付宝/银联网关接入后，将替换为对应的实现类。
 */
class MockPaymentGateway extends BasePaymentGateway
{
    public function getName(): string
    {
        return 'mock';
    }

    public function buildCredential(Payment $payment): array
    {
        return match ($payment->gateway) {
            'wechat' => [
                'type' => 'qrcode',
                'qrcode_url' => 'weixin://wxpay/mock/' . $payment->payment_no,
            ],
            'alipay' => [
                'type' => 'qrcode',
                'qrcode_url' => 'https://qr.alipay.com/mock/' . $payment->payment_no,
            ],
            'unionpay' => [
                'type' => 'redirect',
                'redirect_url' => 'https://unionpay.com/mock/' . $payment->payment_no,
            ],
            default => [
                'type' => 'qrcode',
                'qrcode_url' => 'https://mock.qrcode/' . $payment->payment_no,
            ],
        };
    }
}
