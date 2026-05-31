<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $item = OrderItem::factory()->create();

        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
    }

    /** @test */
    public function it_belongs_to_order(): void
    {
        $item = OrderItem::factory()->create();

        $this->assertInstanceOf(Order::class, $item->order);
    }

    /** @test */
    public function it_belongs_to_product(): void
    {
        $item = OrderItem::factory()->create();

        $this->assertInstanceOf(Product::class, $item->product);
    }

    /** @test */
    public function it_casts_prices_to_money(): void
    {
        $item = OrderItem::factory()->create([
            'unit_price' => 1500,
            'total_price' => 15000,
        ]);

        $this->assertInstanceOf(Money::class, $item->unit_price);
        $this->assertInstanceOf(Money::class, $item->total_price);
        $this->assertSame(15.0, $item->unit_price->toYuan());
        $this->assertSame(150.0, $item->total_price->toYuan());
    }
}
