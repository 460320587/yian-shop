<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Enums\OrderStatus;
use App\Events\OrderStatusChanged;

class SendOrderNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $newStatus = OrderStatus::tryFrom($event->newStatus);

        if (! $newStatus) {
            return;
        }

        $messages = [
            OrderStatus::Paid->value => ['title' => '订单已付款', 'content' => "您的订单 {$order->order_no} 已付款成功，我们将尽快安排生产。"],
            OrderStatus::InProduction->value => ['title' => '订单生产中', 'content' => "您的订单 {$order->order_no} 已进入生产环节。"],
            OrderStatus::Shipped->value => ['title' => '订单已发货', 'content' => "您的订单 {$order->order_no} 已发货，请注意查收。"],
            OrderStatus::Completed->value => ['title' => '订单已完成', 'content' => "您的订单 {$order->order_no} 已完成，感谢您的信任。"],
            OrderStatus::Cancelled->value => ['title' => '订单已取消', 'content' => "您的订单 {$order->order_no} 已取消。"],
            OrderStatus::Refunded->value => ['title' => '订单已退款', 'content' => "您的订单 {$order->order_no} 已退款成功。"],
        ];

        $message = $messages[$newStatus->value] ?? null;

        if (! $message) {
            return;
        }

        CustomerNotification::create([
            'customer_id' => $order->customer_id,
            'type' => 'order',
            'title' => $message['title'],
            'content' => $message['content'],
            'is_read' => 0,
            'action_url' => "/orders/{$order->id}",
            'action_text' => '查看订单',
        ]);
    }
}
