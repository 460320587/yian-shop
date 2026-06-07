<?php

declare(strict_types=1);

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Models\Payment;

/**
 * 支付网关接口
 *
 * 所有支付渠道（微信、支付宝、Mock 等）必须实现此接口。
 * 当前项目处于开发阶段，真实网关暂未接入，使用 MockPaymentGateway 作为占位实现。
 */
interface PaymentGatewayInterface
{
    /**
     * 获取网关名称标识
     */
    public function getName(): string;

    /**
     * 为支付单构建支付凭证
     *
     * @param Payment $payment 已创建的支付单
     * @return array 凭证数据，格式如：
     *               ['type' => 'qrcode', 'qrcode_url' => '...']
     *               ['type' => 'redirect', 'redirect_url' => '...']
     */
    public function buildCredential(Payment $payment): array;
}
