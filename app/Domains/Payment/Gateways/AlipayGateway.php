<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Support\AlipaySigner;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * 支付宝网关（当面付）
 *
 * 调用支付宝预创建 API 生成二维码。
 * 需要配置 payment.alipay.* 才能正常工作。
 */
class AlipayGateway extends BasePaymentGateway
{
    public function getName(): string
    {
        return 'alipay';
    }

    public function buildCredential(Payment $payment): array
    {
        $config = config('payment.alipay');
        if (empty($config) || empty($config['app_id']) || empty($config['private_key'])) {
            throw new InvalidArgumentException('Alipay configuration missing');
        }

        $bizContent = json_encode([
            'out_trade_no' => $payment->payment_no,
            'total_amount' => (string) $payment->amount->toYuan(),
            'subject' => '怡安印刷商城订单',
        ], JSON_UNESCAPED_UNICODE);

        $params = [
            'app_id' => $config['app_id'],
            'method' => 'alipay.trade.precreate',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $config['notify_url'] ?? '',
            'biz_content' => $bizContent,
        ];

        $signer = new AlipaySigner($config['private_key']);
        $params['sign'] = $signer->sign($params);

        $response = Http::asForm()->post('https://openapi.alipay.com/gateway.do', $params);

        if ($response->failed()) {
            throw new InvalidArgumentException('Alipay API error: ' . $response->body());
        }

        $data = $response->json();
        $respKey = 'alipay_trade_precreate_response';
        if (empty($data[$respKey]) || ($data[$respKey]['code'] ?? '') !== '10000') {
            throw new InvalidArgumentException('Alipay API business error: ' . json_encode($data[$respKey] ?? $data));
        }

        return [
            'type' => 'qrcode',
            'qrcode_url' => $data[$respKey]['qr_code'],
        ];
    }
}
