<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use Illuminate\Support\Str;

/**
 * Mock 退款网关
 *
 * 开发环境使用的模拟网关，模拟原路返回退款流程。
 * 真实微信/支付宝退款网关接入后，将替换为对应的实现类。
 */
class MockRefundGateway implements RefundGatewayInterface
{
    public function getPath(): string
    {
        return 'original';
    }

    public function refund(RefundRecord $refund): array
    {
        return [
            'status' => 'success',
            'gateway_refund_no' => 'MOCK_REFUND_' . strtoupper(Str::random(12)),
            'gateway_response' => ['mock' => true],
        ];
    }
}
