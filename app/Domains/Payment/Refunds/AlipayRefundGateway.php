<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Support\AlipaySigner;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * 支付宝退款网关
 *
 * 调用支付宝统一收单交易退款接口执行原路退回。
 */
class AlipayRefundGateway implements RefundGatewayInterface
{
    public function getPath(): string
    {
        return 'original';
    }

    public function refund(RefundRecord $refund): array
    {
        $config = config('payment.alipay');
        if (empty($config) || empty($config['app_id']) || empty($config['private_key'])) {
            throw new InvalidArgumentException('Alipay configuration missing');
        }

        $payment = $refund->payment;
        if (! $payment) {
            throw new InvalidArgumentException('Refund missing associated payment');
        }

        $bizContent = json_encode([
            'out_trade_no' => $payment->payment_no,
            'refund_amount' => (string) $refund->amount->toYuan(),
            'out_request_no' => $refund->refund_no,
            'refund_reason' => $refund->reason ?: null,
        ], JSON_UNESCAPED_UNICODE);

        $params = [
            'app_id' => $config['app_id'],
            'method' => 'alipay.trade.refund',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => $bizContent,
        ];

        $signer = new AlipaySigner($config['private_key']);
        $params['sign'] = $signer->sign($params);

        $response = Http::asForm()->post('https://openapi.alipay.com/gateway.do', $params);

        if ($response->failed()) {
            throw new InvalidArgumentException('Alipay refund API error: ' . $response->body());
        }

        $data = $response->json();
        $respKey = 'alipay_trade_refund_response';
        $resp = $data[$respKey] ?? [];

        if (($resp['code'] ?? '') !== '10000') {
            throw new InvalidArgumentException('Alipay refund business error: ' . json_encode($resp));
        }

        return [
            'status' => 'success',
            'gateway_refund_no' => $refund->refund_no,
            'gateway_response' => $data,
        ];
    }
}
