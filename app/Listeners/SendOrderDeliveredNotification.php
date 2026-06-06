<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Notification\Models\CustomerNotification;
use App\Events\OrderDelivered;

class SendOrderDeliveredNotification
{
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        $trackingNo = $event->trackingNo ?? '';

        $content = "您的订单 {$order->order_no} 已发货";
        if ($trackingNo) {
            $content .= "，物流单号: {$trackingNo}";
        }
        $content .= "。请注意查收。";

        CustomerNotification::create([
            'customer_id' => $order->customer_id,
            'type' => 'logistics',
            'title' => '订单已发货',
            'content' => $content,
            'is_read' => 0,
            'action_url' => "/orders/{$order->id}",
            'action_text' => '查看物流',
        ]);
    }
}
