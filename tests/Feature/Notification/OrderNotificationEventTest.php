<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Domains\Notification\Models\CustomerNotification;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderNotificationEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_change_creates_notification(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        $this->assertDatabaseCount('customer_notifications', 0);

        $order->update([
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $this->assertDatabaseHas('customer_notifications', [
            'customer_id' => $customer->id,
            'type' => 'order',
            'title' => '订单已付款',
        ]);
    }

    public function test_notification_contains_order_info(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        $order->update([
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $notification = CustomerNotification::where('customer_id', $customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString($order->order_no, $notification->content);
        $this->assertStringContainsString('已付款', $notification->content);
    }

    public function test_same_status_update_does_not_create_duplicate_notification(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'out_status_name' => OrderStatus::PendingPayment->label(),
        ]);

        // 第一次更新状态
        $order->update([
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        // 再次更新为相同状态
        $order->update([
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $notifications = \App\Domains\Notification\Models\CustomerNotification::all();
        foreach ($notifications as $n) {
            fwrite(STDERR, $n->title . ' | ' . $n->content . "\n");
        }
        fwrite(STDERR, 'Status logs: ' . \App\Domains\Order\Models\OrderStatusLog::count() . "\n");
        foreach (\App\Domains\Order\Models\OrderStatusLog::all() as $log) {
            fwrite(STDERR, 'Log: from=' . $log->from_status . ' to=' . $log->to_status . "\n");
        }
        $this->assertDatabaseCount('customer_notifications', 1);
    }

    public function test_order_shipped_creates_shipped_notification(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
            'out_status_name' => OrderStatus::Paid->label(),
        ]);

        $order->update([
            'status' => OrderStatus::Shipped->value,
            'out_status_name' => OrderStatus::Shipped->label(),
        ]);

        $notification = CustomerNotification::where('customer_id', $customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('已发货', $notification->content);
    }

    public function test_order_completed_creates_completed_notification(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
            'out_status_name' => OrderStatus::Shipped->label(),
        ]);

        $order->update([
            'status' => OrderStatus::Completed->value,
            'out_status_name' => OrderStatus::Completed->label(),
        ]);

        $notification = CustomerNotification::where('customer_id', $customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('已完成', $notification->content);
    }
}
