<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Models\RefundRecord;
use App\Domains\Payment\Refunds\RefundGatewayFactory;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;

class ProcessRefundAction extends BaseAction
{
    public function __construct(
        private readonly RefundRecord $refund,
    ) {
    }

    public function handle(): void
    {
        $this->transaction(function (): void {
            $customer = $this->refund->customer;
            if (! $customer) {
                throw new BusinessException(ErrorCode::USER_NOT_FOUND);
            }

            // 流转到处理中
            $this->refund->stateMachine()->transition($this->refund, 3, [
                'remark' => '开始执行退款入账',
            ]);

            $gateway = RefundGatewayFactory::make($this->refund->refund_path);
            $response = $gateway->refund($this->refund);

            // 记录网关退款单号
            if (! empty($response['gateway_refund_no'])) {
                $this->refund->update(['gateway_refund_no' => $response['gateway_refund_no']]);
            }

            // 流转到已完成（afterTransition 会自动设置 completed_at）
            $this->refund->stateMachine()->transition($this->refund, 4, [
                'remark' => '退款已到账',
            ]);
        });
    }
}
