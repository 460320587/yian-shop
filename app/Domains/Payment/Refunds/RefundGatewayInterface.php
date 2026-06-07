<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;

/**
 * 退款网关接口
 *
 * 各退款路径（原路返回/钱包/银行卡）实现此接口以执行具体的退款操作。
 */
interface RefundGatewayInterface
{
    /**
     * 获取退款路径标识
     */
    public function getPath(): string;

    /**
     * 执行退款
     *
     * @param RefundRecord $refund 退款记录
     * @return array 网关响应数据，可包含 gateway_refund_no 等
     * @throws \App\Exceptions\BusinessException 退款失败
     */
    public function refund(RefundRecord $refund): array;
}
