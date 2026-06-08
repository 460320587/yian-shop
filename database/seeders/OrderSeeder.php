<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $orders = [
            [
                'order_no' => 'Y202601010001',
                'customer_id' => $customers[0]->id,
                'status' => 11, // 待付款
                'total_amount' => 25000,
                'deposit_sum' => 0,
                'discount_sum' => 0,
                'remark' => '请尽快发货',
                'items' => [
                    ['product_id' => $products[0]->id, 'product_name' => $products[0]->name, 'quantity' => 100, 'unit_price' => 250, 'total_price' => 25000],
                ],
            ],
            [
                'order_no' => 'Y202601010002',
                'customer_id' => $customers[1]->id,
                'status' => 12, // 已付款
                'total_amount' => 50000,
                'deposit_sum' => 50000,
                'discount_sum' => 0,
                'paid_at' => now()->subDays(2),
                'items' => [
                    ['product_id' => $products[1]->id ?? $products[0]->id, 'product_name' => $products[1]->name ?? $products[0]->name, 'quantity' => 200, 'unit_price' => 250, 'total_price' => 50000],
                ],
            ],
            [
                'order_no' => 'Y202601010003',
                'customer_id' => $customers[0]->id,
                'status' => 13, // 生产中
                'total_amount' => 80000,
                'deposit_sum' => 80000,
                'discount_sum' => 0,
                'paid_at' => now()->subDays(3),
                'items' => [
                    ['product_id' => $products[0]->id, 'product_name' => $products[0]->name, 'quantity' => 300, 'unit_price' => 250, 'total_price' => 75000],
                    ['product_id' => $products[1]->id ?? $products[0]->id, 'product_name' => $products[1]->name ?? $products[0]->name, 'quantity' => 1, 'unit_price' => 5000, 'total_price' => 5000],
                ],
            ],
            [
                'order_no' => 'Y202601010004',
                'customer_id' => $customers[2]->id,
                'status' => 20, // 已发货
                'total_amount' => 15000,
                'deposit_sum' => 15000,
                'discount_sum' => 0,
                'paid_at' => now()->subDays(5),
                'express_company' => '顺丰速运',
                'items' => [
                    ['product_id' => $products[0]->id, 'product_name' => $products[0]->name, 'quantity' => 50, 'unit_price' => 300, 'total_price' => 15000],
                ],
            ],
            [
                'order_no' => 'Y202601010005',
                'customer_id' => $customers[1]->id,
                'status' => 60, // 已完成
                'total_amount' => 35000,
                'deposit_sum' => 35000,
                'discount_sum' => 0,
                'paid_at' => now()->subDays(10),
                'items' => [
                    ['product_id' => $products[0]->id, 'product_name' => $products[0]->name, 'quantity' => 100, 'unit_price' => 250, 'total_price' => 25000],
                    ['product_id' => $products[1]->id ?? $products[0]->id, 'product_name' => $products[1]->name ?? $products[0]->name, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000],
                ],
            ],
            [
                'order_no' => 'Y202601010006',
                'customer_id' => $customers[3]->id,
                'status' => 61, // 已取消
                'total_amount' => 10000,
                'deposit_sum' => 0,
                'discount_sum' => 0,
                'items' => [
                    ['product_id' => $products[0]->id, 'product_name' => $products[0]->name, 'quantity' => 40, 'unit_price' => 250, 'total_price' => 10000],
                ],
            ],
        ];

        foreach ($orders as $data) {
            $items = $data['items'];
            unset($data['items']);

            $order = Order::updateOrCreate(['order_no' => $data['order_no']], $data);

            foreach ($items as $item) {
                OrderItem::updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $item['product_id']],
                    $item
                );
            }
        }
    }
}
