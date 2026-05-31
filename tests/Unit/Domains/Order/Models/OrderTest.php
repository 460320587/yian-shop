<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Order\Models;

use App\Domains\Common\ValueObjects\Money;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\User\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_via_factory(): void
    {
        $order = Order::factory()->create();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_belongs_to_customer(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Customer::class, $order->customer);
    }

    /** @test */
    public function it_has_many_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(2)->create(['order_id' => $order->id]);

        $this->assertCount(2, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }

    /** @test */
    public function it_casts_amounts_to_money(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 100000,
            'deposit_sum' => 50000,
            'discount_sum' => 5000,
        ]);

        $this->assertInstanceOf(Money::class, $order->total_amount);
        $this->assertInstanceOf(Money::class, $order->deposit_sum);
        $this->assertInstanceOf(Money::class, $order->discount_sum);
        $this->assertSame(1000.0, $order->total_amount->toYuan());
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $order = Order::factory()->create([
            'paid_at' => '2025-06-01 12:00:00',
            'submitted_at' => '2025-06-01 10:00:00',
        ]);

        $this->assertSame('2025-06-01 12:00:00', $order->paid_at->format('Y-m-d H:i:s'));
    }
}
