<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Order\Models\OrderStatusLog;
use App\Events\OrderStatusChanged;

class WriteOrderStatusLog
{
    public function handle(OrderStatusChanged $event): void
    {
        OrderStatusLog::create([
            'order_id' => $event->order->id,
            'from_status' => $event->oldStatus,
            'to_status' => $event->newStatus,
            'operator_type' => 'system',
        ]);
    }
}
