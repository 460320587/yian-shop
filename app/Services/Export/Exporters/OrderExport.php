<?php

declare(strict_types=1);

namespace App\Services\Export\Exporters;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Services\Export\Contracts\Exportable;

class OrderExport implements Exportable
{
    public function getHeaders(): array
    {
        return ['订单编号', '客户', '状态', '总金额', '定金', '优惠', '下单时间'];
    }

    public function toExportRows(): \Generator
    {
        $orders = Order::with('customer')->cursor();

        foreach ($orders as $order) {
            yield [
                $order->order_no,
                $order->customer?->nickname ?? '未知',
                OrderStatus::from((int) $order->status)->label(),
                $order->total_amount->formatted(),
                $order->deposit_sum->formatted(),
                $order->discount_sum->formatted(),
                $order->created_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }
}
