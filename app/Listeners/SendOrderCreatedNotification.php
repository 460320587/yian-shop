<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Notification\Models\CustomerNotification;
use App\Events\OrderCreated;

class SendOrderCreatedNotification
{
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        CustomerNotification::create([
            'customer_id' => $order->customer_id,
            'type' => 'order',
            'title' => '订单已创建',
            'content' => "您的订单 {$order->order_no} 已创建成功，请尽快完成支付。",
            'is_read' => 0,
            'action_url' => "/orders/{$order->id}",
            'action_text' => '去支付',
        ]);
    }
}
