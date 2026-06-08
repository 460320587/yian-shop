<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Payment\Models\Payment;
use App\Domains\Product\Models\Inventory;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use App\Events\PaymentSuccess;
use App\Listeners\Order\DeductInventoryListener;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeductInventoryListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deducts_inventory_on_payment_success(): void
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
            'order_no' => 'Y202601010001',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);
        $order->load('items');

        // Pre-reserve inventory
        (new InventoryService())->reserve($order);

        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'customer_id' => $customer->id,
            'amount' => 30000,
            'status' => 1,
        ]);

        $listener = new DeductInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new PaymentSuccess($payment));

        $inventory = Inventory::where('product_id', $product->id)->first();
        $this->assertEquals(70, $inventory->available_qty);
        $this->assertEquals(0, $inventory->reserved_qty);
        $this->assertEquals(30, $inventory->locked_qty);
    }

    public function test_it_skips_when_order_has_no_items(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_no' => 'Y202601010002',
        ]);

        $payment = Payment::factory()->create([
            'order_no' => $order->order_no,
            'customer_id' => $customer->id,
            'amount' => 10000,
            'status' => 1,
        ]);

        $listener = new DeductInventoryListener(new \App\Services\Inventory\InventoryService());
        $listener->handle(new PaymentSuccess($payment)); // should not throw

        $this->assertTrue(true); // no exception = pass
    }
}
