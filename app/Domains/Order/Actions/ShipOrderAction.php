<?php

declare(strict_types=1);

namespace App\Domains\Order\Actions;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;

class ShipOrderAction extends BaseAction
{
    public function __construct(
        private readonly Order $order,
        private readonly int $adminId,
        private readonly string $expressCompany,
        private readonly ?string $trackingNo = null,
    ) {
    }

    public function handle()
    {
        $allowedStatuses = [OrderStatus::Paid->value, OrderStatus::PendingDelivery->value];
        if (! in_array((int) $this->order->status, $allowedStatuses, true)) {
            throw new BusinessException(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许发货');
        }

        $this->transaction(function (): void {
            $this->order->stateMachine()->transition($this->order, OrderStatus::Shipped->value, [
                'operator_type' => 'admin',
                'operator_id' => $this->adminId,
                'remark' => '管理员发货: ' . $this->expressCompany,
                'tracking_no' => $this->trackingNo,
            ]);

            $this->order->update([
                'express_company' => $this->expressCompany,
            ]);

            if ($this->trackingNo !== null && $this->trackingNo !== '') {
                OrderDelivery::create([
                    'order_id' => $this->order->id,
                    'carrier_name' => $this->expressCompany,
                    'tracking_no' => $this->trackingNo,
                    'status' => 1,
                    'shipped_at' => now(),
                ]);
            }
        });
    }
}
