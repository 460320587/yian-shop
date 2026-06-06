<?php

declare(strict_types=1);

namespace App\Domains\Order\Actions;

use App\Domains\Coupon\Models\CustomerCoupon;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Exceptions\BusinessException;
use App\Infrastructure\Actions\BaseAction;
use App\Support\ErrorCode;

class CancelOrderAction extends BaseAction
{
    public function __construct(private readonly Order $order)
    {
    }

    public function handle()
    {
        $status = OrderStatus::from((int) $this->order->status);

        if (! $status->canCancel()) {
            throw new BusinessException(ErrorCode::ORDER_STATUS_INVALID, '当前订单状态不允许取消');
        }

        $this->transaction(function (): void {
            $this->order->stateMachine()->transition($this->order, OrderStatus::Cancelled->value, [
                'operator_type' => 'customer',
                'operator_id' => null,
                'remark' => '客户取消订单',
            ]);

            $this->restoreCoupon();
        });
    }

    private function restoreCoupon(): void
    {
        if (! $this->order->customer_coupon_id) {
            return;
        }

        $customerCoupon = CustomerCoupon::with('coupon')->find($this->order->customer_coupon_id);

        if ($customerCoupon && $customerCoupon->status === 2) {
            $customerCoupon->update([
                'status' => 1,
                'used_at' => null,
            ]);

            if ($customerCoupon->coupon) {
                $customerCoupon->coupon->decrement('used_count');
            }
        }
    }
}
