<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCancelOrderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * 自动取消超时未付款订单（24小时）
     */
    public function handle(): void
    {
        $cutoff = now()->subHours(24);

        Order::where('status', OrderStatus::PendingPayment->value)
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $this->cancelOrder($order);
                }
            });
    }

    private function cancelOrder(Order $order): void
    {
        try {
            $order->stateMachine()->transition($order, OrderStatus::Cancelled->value, [
                'operator_type' => 'system',
                'operator_id' => null,
                'remark' => '超时未付款，系统自动取消',
            ]);
        } catch (\Throwable $e) {
            // Log but don't fail the batch — individual order cancellation errors
            // (e.g. race condition where user paid just before cancellation)
            // should not block other orders from being cancelled.
            report($e);
        }
    }
}
