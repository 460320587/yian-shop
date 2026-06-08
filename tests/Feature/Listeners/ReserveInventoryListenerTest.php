<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Events\OrderCreated;
use App\Listeners\Order\ReserveInventoryListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReserveInventoryListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reserves_inventory_on_order_created(): void
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
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 25,
        ]);
        $order->load('items');

        $listener = new ReserveInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new OrderCreated($order));

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(75, $inventory->available_qty);
        $this->assertEquals(25, $inventory->reserved_qty);
    }

    public function test_it_throws_exception_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['status' => 1]);
        Inventory::create([
            'product_id' => $product->id,
            'available_qty' => 10,
            'reserved_qty' => 0,
            'locked_qty' => 0,
            'safety_stock' => 10,
            'version' => 0,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 25,
        ]);
        $order->load('items');

        $this->expectException(\App\Exceptions\InsufficientInventoryException::class);

        $listener = new ReserveInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new OrderCreated($order));
    }
}
