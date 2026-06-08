<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Order\Models\Order;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\RefundRecord;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Seeder;

class RefundRecordSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::whereIn('status', [60, 61, 65])->get();
        $customers = Customer::all();

        if ($orders->isEmpty() || $customers->isEmpty()) {
            return;
        }

        // Create a dummy payment record to satisfy FK constraint
        $payment = Payment::updateOrCreate(
            ['payment_no' => 'P20260101001'],
            [
                'order_no' => $orders[0]->order_no,
                'customer_id' => $orders[0]->customer_id,
                'gateway' => 'wechat',
                'amount' => 25000,
                'status' => 1,
                'paid_at' => now()->subDays(2),
            ]
        );

        $refunds = [
            [
                'order_id' => $orders[0]->id,
                'payment_id' => $payment->id,
                'customer_id' => $orders[0]->customer_id,
                'refund_no' => 'R20260101001',
                'amount' => 25000,
                'reason' => '商品质量问题',
                'status' => 0, // 待审核
            ],
            [
                'order_id' => $orders[0]->id,
                'payment_id' => $payment->id,
                'customer_id' => $orders[0]->customer_id,
                'refund_no' => 'R20260101002',
                'amount' => 5000,
                'reason' => '数量不足补退',
                'status' => 4, // 已完成
                'completed_at' => now()->subDays(2),
            ],
            [
                'order_id' => $orders[1]->id ?? $orders[0]->id,
                'payment_id' => $payment->id,
                'customer_id' => ($orders[1] ?? $orders[0])->customer_id,
                'refund_no' => 'R20260101003',
                'amount' => 10000,
                'reason' => '客户取消订单',
                'status' => 1, // 已审核
                'approved_at' => now()->subDays(1),
            ],
        ];

        foreach ($refunds as $data) {
            RefundRecord::updateOrCreate(['refund_no' => $data['refund_no']], $data);
        }
    }
}
