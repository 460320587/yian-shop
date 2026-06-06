<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\AfterSale\Actions;

use App\Domains\AfterSale\Actions\ApplyAfterSaleAction;
use App\Domains\AfterSale\Models\AfterSale;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyAfterSaleActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_after_sale_with_items(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_price' => 2500,
        ]);

        $action = new ApplyAfterSaleAction($customer->id, [
            'order_no' => $order->order_no,
            'type' => 1,
            'reason' => '质量问题',
            'items' => [
                ['order_item_id' => $orderItem->id, 'quantity' => 2],
            ],
        ]);

        $afterSale = $action->handle();

        $this->assertInstanceOf(AfterSale::class, $afterSale);
        $this->assertEquals(1, $afterSale->status);
        $this->assertDatabaseHas('after_sale_items', [
            'after_sale_id' => $afterSale->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 2,
        ]);
    }
}
