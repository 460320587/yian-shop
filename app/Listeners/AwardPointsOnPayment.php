<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Points\Models\CustomerPointsLog;
use App\Domains\User\Models\Customer;
use App\Events\PaymentSuccess;

class AwardPointsOnPayment
{
    /**
     * 支付成功奖励积分（每消费 1 元得 1 积分）
     */
    public function handle(PaymentSuccess $event): void
    {
        $payment = $event->payment;
        $amountYuan = (int) ($payment->amount->amount / 100);

        if ($amountYuan <= 0) {
            return;
        }

        $customer = Customer::find($payment->customer_id);
        if ($customer === null) {
            return;
        }

        $balanceBefore = $customer->points;
        $balanceAfter = $balanceBefore + $amountYuan;

        CustomerPointsLog::create([
            'customer_id' => $payment->customer_id,
            'type' => 1, // 获得
            'points' => $amountYuan,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'order_no' => $payment->order_no,
            'remark' => "支付成功奖励 {$amountYuan} 积分",
        ]);

        $customer->update([
            'points' => $balanceAfter,
            'grow_value' => $customer->grow_value + $amountYuan,
        ]);
    }
}
