<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Invoice\Models\Invoice;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::where('status', '>=', 12)->get(); // 已付款及以上
        $customers = Customer::all();

        if ($orders->isEmpty() || $customers->isEmpty()) {
            return;
        }

        $invoices = [
            [
                'order_id' => $orders[0]->id,
                'customer_id' => $orders[0]->customer_id,
                'invoice_no' => 'INV20260101001',
                'type' => 0, // 普通发票
                'status' => 1, // 待开具
                'business_type' => 1,
                'title' => '怡安印刷有限公司',
                'tax_number' => '91410100MA12345678',
                'amount' => 25000,
                'email' => 'finance@yian.com',
                'address' => '河南省郑州市金水区花园路 100 号',
            ],
            [
                'order_id' => $orders[1]->id ?? $orders[0]->id,
                'customer_id' => ($orders[1] ?? $orders[0])->customer_id,
                'invoice_no' => 'INV20260101002',
                'type' => 1, // 专用发票
                'status' => 4, // 已开具
                'business_type' => 1,
                'title' => '智印科技有限公司',
                'tax_number' => '91440300MA87654321',
                'amount' => 50000,
                'email' => 'invoice@zhiyin.com',
                'address' => '广东省深圳市南山区科技园南路 88 号',
                'bank_name' => '招商银行深圳分行',
                'bank_account' => '7559123456789012',
                'issued_at' => now()->subDays(3),
            ],
            [
                'order_id' => $orders[2]->id ?? $orders[0]->id,
                'customer_id' => ($orders[2] ?? $orders[0])->customer_id,
                'invoice_no' => 'INV20260101003',
                'type' => 2, // 电子发票
                'status' => 1, // 待开具
                'business_type' => 1,
                'title' => '张三（个人）',
                'tax_number' => '',
                'amount' => 80000,
                'email' => 'zhangsan@example.com',
            ],
        ];

        foreach ($invoices as $data) {
            Invoice::updateOrCreate(['invoice_no' => $data['invoice_no']], $data);
        }
    }
}
