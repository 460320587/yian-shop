<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Support\WechatPaySigner;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * 微信支付退款网关
 *
 * 调用微信 V3 退款 API 执行原路退回。
 */
class WechatPayRefundGateway implements RefundGatewayInterface
{
    public function getPath(): string
    {
        return 'original';
    }

    public function refund(RefundRecord $refund): array
    {
        $config = config('payment.wechatpay');
        if (empty($config) || empty($config['mch_id']) || empty($config['private_key'])) {
            throw new InvalidArgumentException('WeChat Pay configuration missing');
        }

        $payment = $refund->payment;
        if (! $payment) {
            throw new InvalidArgumentException('Refund missing associated payment');
        }

        $path = '/v3/refund/domestic/refunds';
        $body = json_encode([
            'out_refund_no' => $refund->refund_no,
            'out_trade_no' => $payment->payment_no,
            'amount' => [
                'refund' => $refund->amount->amount,
                'total' => $payment->amount->amount,
                'currency' => 'CNY',
            ],
            'reason' => $refund->reason ?: null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signer = new WechatPaySigner($config['private_key']);
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
            throw new InvalidArgumentException('WeChat Pay refund API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'status' => ($data['status'] ?? '') === 'SUCCESS' ? 'success' : 'processing',
            'gateway_refund_no' => $data['refund_id'] ?? null,
            'gateway_response' => $data,
        ];
    }
}
