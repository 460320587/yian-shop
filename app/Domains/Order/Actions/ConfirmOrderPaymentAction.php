<?php

declare(strict_types=1);

namespace App\Domains\Order\Actions;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;

class ConfirmOrderPaymentAction extends BaseAction
{
    public function __construct(
        private readonly Order $order,
        private readonly int $adminId,
    ) {
    }

    public function handle()
    {
        if ((int) $this->order->status !== OrderStatus::PendingPayment->value) {
            throw new BusinessException(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许确认付款');
        }

        $this->order->stateMachine()->transition($this->order, OrderStatus::Paid->value, [
            'operator_type' => 'admin',
            'operator_id' => $this->adminId,
            'remark' => '管理员确认付款',
        ]);
    }
}
