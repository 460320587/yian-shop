<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Jobs\AutoCancelOrderJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCancelOrderJobTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(int $hoursAgo = 25): Order
    {
        $product = Product::factory()->create(['status' => 1]);
        Inventory::create([
            'product_id' => $product->id,
            'available_qty' => 100,
            'reserved_qty' => 0,
            'locked_qty' => 0,
            'safety_stock' => 10,
            'version' => 0,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'created_at' => now()->subHours($hoursAgo),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        return $order->load('items');
    }

    public function test_it_cancels_overdue_pending_orders(): void
    {
        $order = $this->createPendingOrder(25);

        // Pre-reserve inventory
        (new \App\Services\Inventory\InventoryService())->reserve($order);

        $job = new AutoCancelOrderJob();
        $job->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->status);
    }

    public function test_it_releases_inventory_on_auto_cancel(): void
    {
        $product = Product::factory()->create(['status' => 1]);
        Inventory::create([
            'product_id' => $product->id,
            'available_qty' => 100,
            'reserved_qty' => 0,
            'locked_qty' => 0,
            'safety_stock' => 10,
            'version' => 0,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::PendingPayment->value,
            'created_at' => now()->subHours(25),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);
        $order->load('items');

        // Pre-reserve
        (new \App\Services\Inventory\InventoryService())->reserve($order);
        $this->assertEquals(70, Inventory::where('product_id', $product->id)->value('available_qty'));

        $job = new AutoCancelOrderJob();
        $job->handle();

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(100, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
    }

    public function test_it_does_not_cancel_recent_orders(): void
    {
        $order = $this->createPendingOrder(1);

        $job = new AutoCancelOrderJob();
        $job->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::PendingPayment->value, $order->status);
    }

    public function test_it_does_not_cancel_paid_orders(): void
    {
        $product = Product::factory()->create(['status' => 1]);
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Paid->value,
            'created_at' => now()->subHours(25),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $job = new AutoCancelOrderJob();
        $job->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid->value, $order->status);
    }

    public function test_it_records_system_cancel_remark(): void
    {
        $order = $this->createPendingOrder(25);

        $job = new AutoCancelOrderJob();
        $job->handle();

        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::Cancelled->value,
        ]);
    }
}
