<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoConfirmReceiptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * 自动确认收货天数阈值
     */
    private int $thresholdDays = 7;

    /**
     * 自动确认发货后超过阈值的订单为已完成
     */
    public function handle(): void
    {
        $cutoff = now()->subDays($this->thresholdDays);

        // 1. 有 delivery 记录且 shipped_at 超期
        $orderIds = OrderDelivery::where('shipped_at', '<', $cutoff)
            ->whereNull('delivered_at')
            ->pluck('order_id')
            ->all();

        // 2. 没有 delivery 记录但 status=Shipped 且 updated_at 超期（兼容旧数据）
        $orphanOrderIds = Order::where('status', OrderStatus::Shipped->value)
            ->where('updated_at', '<', $cutoff)
            ->whereNotIn('id', $orderIds)
            ->whereDoesntHave('orderDeliveries')
            ->pluck('id')
            ->all();

        $allOrderIds = array_merge($orderIds, $orphanOrderIds);

        if ($allOrderIds === []) {
            return;
        }

        Order::whereIn('id', $allOrderIds)
            ->where('status', OrderStatus::Shipped->value)
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->confirmReceipt($order);
                }
            });
    }

    private function confirmReceipt(Order $order): void
    {
        try {
            $order->stateMachine()->transition($order, OrderStatus::Completed->value, [
                'operator_type' => 'system',
                'operator_id' => null,
                'remark' => '发货后超过' . $this->thresholdDays . '天未确认，系统自动完成',
            ]);

            $delivery = OrderDelivery::where('order_id', $order->id)->first();
            if ($delivery && $delivery->delivered_at === null) {
                $delivery->update(['delivered_at' => now()]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
