<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\InventoryLog;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Exceptions\InsufficientInventoryException;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    private function createProductWithInventory(int $qty): Product
    {
        $product = Product::factory()->create(['status' => 1]);
        Inventory::create([
            'product_id' => $product->id,
            'available_qty' => $qty,
            'reserved_qty' => 0,
            'locked_qty' => 0,
            'safety_stock' => 10,
            'version' => 0,
        ]);
        return $product;
    }

    private function createOrderWithItem(Product $product, int $qty): Order
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => 'Y' . now()->format('Ymd') . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $qty,
        ]);
        return $order->load('items');
    }

    public function test_it_reserves_inventory_for_order_items(): void
    {
        $product = $this->createProductWithInventory(100);
        $order = $this->createOrderWithItem($product, 20);

        $this->service->reserve($order);

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(80, $inventory->available_qty);
        $this->assertEquals(20, $inventory->reserved_qty);
        $this->assertEquals(0, $inventory->locked_qty);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'order_no' => $order->order_no,
            'type' => 1,
            'qty_change' => 20,
            'qty_after' => 80,
        ]);
    }

    public function test_it_deducts_inventory_after_payment(): void
    {
        $product = $this->createProductWithInventory(100);
        $order = $this->createOrderWithItem($product, 20);

        $this->service->reserve($order);
        $this->service->deduct($order);

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(80, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
        $this->assertEquals(20, $inventory->locked_qty);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'order_no' => $order->order_no,
            'type' => 2,
            'qty_change' => 20,
        ]);
    }

    public function test_it_releases_inventory_when_order_cancelled(): void
    {
        $product = $this->createProductWithInventory(100);
        $order = $this->createOrderWithItem($product, 20);

        $this->service->reserve($order);
        $this->service->release($order);

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(100, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
        $this->assertEquals(0, $inventory->locked_qty);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'order_no' => $order->order_no,
            'type' => 3,
            'qty_change' => 20,
        ]);
    }

    public function test_it_throws_exception_when_inventory_insufficient(): void
    {
        $product = $this->createProductWithInventory(10);
        $order = $this->createOrderWithItem($product, 20);

        $this->expectException(InsufficientInventoryException::class);
        $this->service->reserve($order);
    }

    public function test_reserve_does_not_change_state_on_insufficient_inventory(): void
    {
        $product = $this->createProductWithInventory(10);
        $order = $this->createOrderWithItem($product, 20);

        try {
            $this->service->reserve($order);
        } catch (InsufficientInventoryException) {
            // expected
        }

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(10, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);

        // No log should be written for failed reservation
        $this->assertDatabaseCount('inventory_logs', 0);
    }

    public function test_it_prevents_race_condition_with_optimistic_locking(): void
    {
        $product = $this->createProductWithInventory(100);
        $order1 = $this->createOrderWithItem($product, 60);
        $order2 = $this->createOrderWithItem($product, 60);

        $this->service->reserve($order1);

        // Second reservation should fail because only 40 available after first reservation
        $this->expectException(InsufficientInventoryException::class);
        $this->service->reserve($order2);
    }

    public function test_it_releases_only_reserved_quantity(): void
    {
        $product = $this->createProductWithInventory(100);
        $order = $this->createOrderWithItem($product, 20);

        // Reserve then deduct (so reserved becomes 0, locked becomes 20)
        $this->service->reserve($order);
        $this->service->deduct($order);

        // Release should not affect already-deducted inventory
        $this->service->release($order);

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(80, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
        $this->assertEquals(20, $inventory->locked_qty);
    }
}
