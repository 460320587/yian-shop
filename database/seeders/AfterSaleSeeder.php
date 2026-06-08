<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Seeder;

class AfterSaleSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::whereIn('status', [60, 61])->get(); // 已完成或已取消的订单
        $customers = Customer::all();

        if ($orders->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $afterSales = [
            [
                'after_sale_no' => 'AS20260101001',
                'order_no' => $orders[0]->order_no,
                'customer_id' => $orders[0]->customer_id,
                'type' => 0, // 退货退款
                'status' => 1, // 待处理
                'reason' => '商品质量问题',
                'description' => '印刷颜色与样品不符',
                'images' => [],
                'refund_amount' => 25000,
                'approved_amount' => 0,
            ],
            [
                'after_sale_no' => 'AS20260101002',
                'order_no' => $orders[0]->order_no,
                'customer_id' => $orders[0]->customer_id,
                'type' => 1, // 补印
                'status' => 5, // 已完成
                'reason' => '数量不足',
                'description' => '实际收到数量比订单少 20 本',
                'images' => [],
                'refund_amount' => 5000,
                'approved_amount' => 5000,
                'completed_at' => now()->subDays(2),
            ],
            [
                'after_sale_no' => 'AS20260101003',
                'order_no' => $orders[1]->order_no ?? $orders[0]->order_no,
                'customer_id' => ($orders[1] ?? $orders[0])->customer_id,
                'type' => 2, // 优惠货款
                'status' => 2, // 处理中
                'reason' => '价格差异',
                'description' => '下单后发现同商品降价',
                'images' => [],
                'refund_amount' => 1000,
                'approved_amount' => 1000,
            ],
        ];

        foreach ($afterSales as $data) {
            AfterSale::updateOrCreate(['after_sale_no' => $data['after_sale_no']], $data);
        }
    }
}
