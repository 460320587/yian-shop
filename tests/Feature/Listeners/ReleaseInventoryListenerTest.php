<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Events\OrderStatusChanged;
use App\Listeners\Order\ReleaseInventoryListener;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseInventoryListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_releases_inventory_when_order_cancelled(): void
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
            'status' => 11,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);
        $order->load('items');

        // Pre-reserve
        (new InventoryService())->reserve($order);

        // Simulate status change to cancelled (61)
        $listener = new ReleaseInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new OrderStatusChanged($order, 11, 61));

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(100, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
        $this->assertEquals(0, $inventory->locked_qty);
    }

    public function test_it_does_nothing_when_status_is_not_cancelled(): void
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
            'status' => 11,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);
        $order->load('items');

        (new InventoryService())->reserve($order);

        // Status change to paid (12) — should not release
        $listener = new ReleaseInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new OrderStatusChanged($order, 11, 12));

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(70, $inventory->available_qty);
        $this->assertEquals(30, $inventory->reserved_qty);
    }
}
