<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Domains\Logistics\Models\OrderDelivery;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoConfirmReceiptCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_confirms_receipt_for_shipped_orders_over_threshold(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);
        OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'shipped_at' => now()->subDays(8),
            'delivered_at' => null,
        ]);

        $this->artisan('orders:auto-confirm-receipt')
            ->assertSuccessful()
            ->execute();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Shipped->value,
            'to_status' => OrderStatus::Completed->value,
            'operator_type' => 'system',
            'remark' => '发货后超过7天未确认，系统自动完成',
        ]);
    }

    public function test_command_skips_recently_shipped_orders(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);
        OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'shipped_at' => now()->subDays(3),
            'delivered_at' => null,
        ]);

        $this->artisan('orders:auto-confirm-receipt')
            ->assertSuccessful()
            ->execute();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Shipped->value,
        ]);
    }

    public function test_command_skips_non_shipped_orders(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);

        $this->artisan('orders:auto-confirm-receipt')
            ->assertSuccessful()
            ->execute();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PendingPayment->value,
        ]);
    }

    public function test_command_sets_delivered_at_on_delivery(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
        ]);
        $delivery = OrderDelivery::factory()->create([
            'order_id' => $order->id,
            'shipped_at' => now()->subDays(10),
            'delivered_at' => null,
        ]);

        $this->artisan('orders:auto-confirm-receipt')
            ->assertSuccessful()
            ->execute();

        $this->assertDatabaseHas('order_deliveries', [
            'id' => $delivery->id,
        ]);
        $this->assertNotNull($delivery->fresh()->delivered_at);
    }

    public function test_command_handles_orders_without_delivery(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Shipped->value,
            'updated_at' => now()->subDays(10),
        ]);
        // 没有 OrderDelivery 记录，但 updated_at 已超期

        $this->artisan('orders:auto-confirm-receipt')
            ->assertSuccessful()
            ->execute();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Completed->value,
        ]);
    }
}
