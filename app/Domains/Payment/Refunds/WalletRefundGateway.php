<?php

declare(strict_types=1);

namespace App\Domains\Payment\Refunds;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Services\WalletService;

/**
 * 钱包退款网关
 *
 * 将退款金额退回客户钱包余额。
 */
class WalletRefundGateway implements RefundGatewayInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function getPath(): string
    {
        return 'wallet';
    }

    public function refund(RefundRecord $refund): array
    {
        $customer = $refund->customer;

        $this->walletService->credit(
            $customer,
            $refund->amount,
            'refund',
            $refund->refund_no,
            '退款: ' . $refund->reason,
        );

        return [
            'status' => 'success',
            'gateway_refund_no' => $refund->refund_no,
        ];
    }
}
