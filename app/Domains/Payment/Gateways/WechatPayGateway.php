<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * 微信支付网关（Native 支付）
 *
 * 调用微信 V3 API 创建支付订单并返回二维码链接。
 * 需要配置 payment.wechatpay.* 才能正常工作。
 */
class WechatPayGateway extends BasePaymentGateway
{
    public function getName(): string
    {
        return 'wechat';
    }

    public function buildCredential(Payment $payment): array
    {
        $config = config('payment.wechatpay');
        if (empty($config) || empty($config['mch_id']) || empty($config['private_key'])) {
            throw new InvalidArgumentException('WeChat Pay configuration missing');
        }

        $signer = new WechatPaySigner($config['private_key']);
        $path = '/v3/pay/transactions/native';
        $body = json_encode([
            'appid' => $config['app_id'] ?? '',
            'mchid' => $config['mch_id'],
            'description' => '怡安印刷商城订单',
            'out_trade_no' => $payment->payment_no,
            'notify_url' => $config['notify_url'] ?? '',
            'amount' => [
                'total' => $payment->amount->amount,
                'currency' => 'CNY',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $authHeader = $signer->buildAuthorizationHeader(
            'POST',
            $path,
            $body,
            $config['mch_id'],
            $config['serial_no'] ?? '',
        );

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.mch.weixin.qq.com' . $path, json_decode($body, true));

        if ($response->failed()) {
            throw new InvalidArgumentException('WeChat Pay API error: ' . $response->body());
        }

        $data = $response->json();
        if (empty($data['code_url'])) {
            throw new InvalidArgumentException('WeChat Pay response missing code_url');
        }

        return [
            'type' => 'qrcode',
            'qrcode_url' => $data['code_url'],
        ];
    }
}
